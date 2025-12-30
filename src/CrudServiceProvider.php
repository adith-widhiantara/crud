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
        $middleware = config('crud.middleware') ?? ['api'];
        $prefix = config('crud.prefix') ?? 'api';

        if (! is_dir($controllerPath)) {
            return;
        }

        // Cari semua file PHP di folder Controllers
        $finder = new Finder;
        $finder->files()->in($controllerPath)->name('*.php');

        Route::middleware($middleware)
            ->prefix($prefix)
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

        $ignoredMethods = [
            'index', 'store', 'show', 'update', 'destroy',
            'service', 'model', 'getRouteKeyName', '__construct',
        ];

        foreach ($methods as $method) {
            $methodName = $method->getName();

            // 1. Skip method standar resource
            if (in_array($methodName, $ignoredMethods)) {
                continue;
            }

            // 2. Cek apakah punya Attribute #[Endpoint]
            $attributes = $method->getAttributes(Endpoint::class);
            $hasEndpointAttribute = ! empty($attributes);

            // 3. Logic Filter Baru:
            // Skip jika method ini warisan dari Parent (bukan ditulis di Child)
            // DAN tidak memiliki attribute #[Endpoint].
            // Artinya: Method parent yang ada #[Endpoint]-nya (seperti bulk) akan LOLOS.
            if ($method->class !== $className && ! $hasEndpointAttribute) {
                continue;
            }

            // --- Mulai Logic Ekstraksi Route ---

            // Default Values
            $httpMethods = ['GET'];
            $uriSegment = Str::kebab($methodName);
            $isCustomUri = false;

            if ($hasEndpointAttribute) {
                $attributeInstance = $attributes[0]->newInstance();

                $httpMethods = (array) $attributeInstance->method;

                if ($attributeInstance->uri) {
                    $uriSegment = $attributeInstance->uri;
                    $isCustomUri = true;
                }
            }

            if (! $isCustomUri) {
                $routeParams = [];
                foreach ($method->getParameters() as $param) {
                    $type = $param->getType();
                    if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                        continue;
                    }
                    $routeParams[] = '{'.$param->getName().'}';
                }

                if (! empty($routeParams)) {
                    $uriSegment .= '/'.implode('/', $routeParams);
                }
            }

            // Register Route
            // Karena 'bulk' didefinisikan di parent, dia akan otomatis tersedia
            // untuk SEMUA controller turunannya.
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
