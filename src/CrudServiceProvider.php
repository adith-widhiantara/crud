<?php

namespace Adithwidhiantara\Crud;

use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
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

        $controllerPath = app_path('Http/Controllers');

        if (! is_dir($controllerPath)) {
            return;
        }

        // Cari semua file PHP di folder Controllers
        $finder = new Finder;
        $finder->files()->in($controllerPath)->name('*.php');

        Route::middleware('api') // Default ke 'api' middleware
            ->prefix('api')      // Default prefix '/api'
            ->group(function () use ($finder) {

                foreach ($finder as $file) {
                    $className = $this->getClassFromFile($file);

                    // Cek apakah class valid dan extend BaseCrudController
                    if (
                        $className &&
                        class_exists($className) &&
                        is_subclass_of($className, BaseCrudController::class) &&
                        ! (new ReflectionClass($className))->isAbstract()
                    ) {
                        // Instantiate controller untuk akses method getRouteKeyName
                        // Kita pakai app()->make agar dependency injection tetap jalan jika ada constructor
                        $controllerInstance = app($className);

                        // Ambil slug, misal: 'products'
                        $slug = $controllerInstance->getRouteKeyName();

                        // Register Route: GET, POST, PUT, DELETE
                        // /api/products
                        Route::apiResource($slug, $className);
                    }
                }
            });
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
