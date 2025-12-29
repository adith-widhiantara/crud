<?php

namespace Adithwidhiantara\Crud;

use Adithwidhiantara\Crud\Attributes\Endpoint;
use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 1. Merge Config (Penting agar default value tetap jalan)
        // Pastikan path-nya mengarah ke file config/crud.php yang baru dibuat
        $this->mergeConfigFrom(
            __DIR__.'/../config/crud.php', 'crud'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Allow user to publish config file
            $this->publishes([
                __DIR__.'/../config/crud.php' => config_path('crud.php'),
            ], 'crud-config');

            // $this->commands([...]);
        }

        // Jalankan auto-discovery route
        $this->registerDynamicRoutes();
    }

    protected function registerDynamicRoutes(): void
    {
        // Jika route sudah di-cache (php artisan route:cache),
        // kita TIDAK BOLEH scan folder lagi. Laravel sudah pegang daftarnya.
        if ($this->app->routesAreCached()) {
            return;
        }

        $controllerPath = config('crud.controllers_path') ?? app_path('Http/Controllers');

        if (! is_dir($controllerPath)) {
            return;
        }

        // Cari semua file PHP di folder Controllers
        $finder = new Finder;
        $finder->files()->in($controllerPath)->name('*.php');

        Route::middleware('api')
            ->prefix('api')
            ->group(function () use ($finder) {
                foreach ($finder as $file) {
                    $className = $this->getClassFromFile($file);

                    // 3. Validasi Class: Harus ada, bukan abstrak, dan extend BaseCrudController
                    if (
                        ! $className ||
                        ! class_exists($className) ||
                        ! is_subclass_of($className, BaseCrudController::class) ||
                        (new ReflectionClass($className))->isAbstract()
                    ) {
                        continue;
                    }

                    // Instantiate dummy controller untuk ambil base slug (misal: 'products')
                    $controllerInstance = app($className);
                    $baseSlug = $controllerInstance->getRouteKeyName();

                    // --- LOGIC CUSTOM ROUTE DISCOVERY ---
                    $this->registerCustomMethods($className, $baseSlug);

                    // --- LOGIC STANDARD CRUD ROUTE ---
                    // Didaftarkan TERAKHIR agar wildcard {id} tidak menimpa custom route
                    Route::apiResource($baseSlug, $className);
                }
            });
    }

    protected function registerCustomMethods(string $className, string $baseSlug): void
    {
        $reflector = new ReflectionClass($className);
        $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

        // Method bawaan yang harus di-skip agar tidak didaftarkan ulang
        $ignoredMethods = [
            'index', 'store', 'show', 'update', 'destroy',
            'service', 'model', 'getRouteKeyName', '__construct',
        ];

        foreach ($methods as $method) {
            $methodName = $method->getName();

            // Filter 1: Skip method bawaan & method milik parent (kecuali di-override)
            if (in_array($methodName, $ignoredMethods) || $method->class !== $className) {
                continue;
            }

            // Default Values
            $httpMethods = ['GET'];
            $uriSegment = Str::kebab($methodName);
            $isCustomUri = false;

            // Cek Attribute #[Endpoint]
            $attributes = $method->getAttributes(Endpoint::class);
            if (! empty($attributes)) {
                $attributeInstance = $attributes[0]->newInstance();

                // Override Method (POST/PUT/dll)
                $httpMethods = (array) $attributeInstance->method;

                // Override URI jika user mendefinisikan
                if ($attributeInstance->uri) {
                    $uriSegment = $attributeInstance->uri;
                    $isCustomUri = true;
                }
            }

            // Logic Auto-Params:
            // Jika user TIDAK set URI manual, kita generate params otomatis dari function arguments.
            // Contoh: public function test($id) -> /test/{id}
            if (! $isCustomUri) {
                $routeParams = [];
                foreach ($method->getParameters() as $param) {
                    $type = $param->getType();

                    // Skip Dependency Injection (Class Object seperti Request)
                    if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                        continue;
                    }

                    // Masukkan Primitive Type (string, int) ke URL
                    $routeParams[] = '{'.$param->getName().'}';
                }

                if (! empty($routeParams)) {
                    $uriSegment .= '/'.implode('/', $routeParams);
                }
            }

            // Register Route Akhir
            // URL: /api/products/custom-method/{param}
            $fullPath = $baseSlug.'/'.$uriSegment;

            Route::match($httpMethods, $fullPath, [$className, $methodName]);
        }
    }

    /**
     * Helper untuk mendapatkan full class name (namespace + class) dari file.
     */
    protected function getClassFromFile(SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getRealPath());

        // Regex sederhana untuk cari namespace dan class name
        if (preg_match('/namespace\s+(.+?);/', $contents, $nsMatches) &&
            preg_match('/class\s+(\w+)/', $contents, $classMatches)) {
            return $nsMatches[1].'\\'.$classMatches[1];
        }

        return null;
    }
}
