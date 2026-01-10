<?php

namespace Adithwidhiantara\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Adithwidhiantara\Crud\Generators\CrudStub;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {name : The name of the model (e.g. Product)}';

    protected $description = 'Generate Model, Service, and Controller for CRUD operation';

    public function handle(): int
    {
        $name = $this->argument('name');
        $name = Str::studly($name); // Pastikan format PascalCase (e.g. UserProfile)
        $tableName = Str::snake(Str::plural($name)); // Format snake_case plural (e.g. user_profiles)

        $this->info("Generating CRUD for: {$name}...");

        // 1. Generate Model
        $this->generateModel($name);

        // 2. Generate Service
        $this->generateService($name);

        // 3. Generate Controller
        $this->generateController($name);

        // 4. Generate Migration File
        $this->generateMigration($tableName);

        // 5. Generate Unit Test File
        $this->generateUnitTest($name);

        $this->info("CRUD for {$name} generated successfully! 🚀");
        $this->comment("Don't forget to run 'php artisan migrate'.");

        return self::SUCCESS; // Return 0 biasanya standar untuk Sukses
    }

    protected function generateModel(string $name): void
    {
        // Pastikan direktori Models ada
        if (!File::exists(app_path('Models'))) {
            File::makeDirectory(app_path('Models'), 0755, true);
        }

        $path = app_path("Models/{$name}.php");

        if (File::exists($path)) {
            $this->warn("Model {$name} already exists. Skipped.");

            return;
        }

        // make factory
        $this->call('make:factory', [
            'name' => $name . 'Factory',
            '--model' => $name,
        ]);

        $content = CrudStub::model($name);

        File::put($path, $content);
        $this->info("✅ Model & Factory created: app/Models/{$name}.php");
    }

    protected function generateService(string $name): void
    {
        $content = CrudStub::service($name);

        $this->createFile(app_path('Http/Services'), "{$name}Service.php", $content, 'Service', "{$name}Service");
    }

    protected function generateController(string $name): void
    {
        $content = CrudStub::controller($name);

        $this->createFile(app_path('Http/Controllers'), "{$name}Controller.php", $content, 'Controller', "{$name}Controller");
    }

    protected function generateMigration(string $tableName): void
    {
        $migrationName = "create_{$tableName}_table";

        $this->info('⏳ Generating migration file...');

        // Memanggil artisan command bawaan Laravel
        // Ini setara dengan menjalankan: php artisan make:migration create_products_table --create=products
        $this->call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName,
        ]);

        // Note: Output sukses migration akan muncul otomatis dari command bawaan tersebut
    }

    protected function generateUnitTest(string $name): void
    {
        $directory = base_path('tests/Feature');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = "{$directory}/{$name}ControllerTest.php";

        if (File::exists($path)) {
            $this->warn("Unit Test {$name}ControllerTest already exists. Skipped.");

            return;
        }

        $routeSlug = Str::plural(Str::kebab($name)); // Product -> products
        $modelClass = "App\\Models\\{$name}";

        // Perbaikan: Namespace standar Laravel 'Tests\Feature'
        // Fitur: Langsung generate 5 method test utama
        $content = CrudStub::unitTest($name, $routeSlug, $modelClass);

        File::put($path, $content);
        $this->info("✅ Unit Test created: tests/Feature/{$name}ControllerTest.php");
    }
    protected function createFile(string $directory, string $filename, string $content, string $type, string $entityName): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = "{$directory}/{$filename}";

        if (File::exists($path)) {
            $this->warn("{$type} {$entityName} already exists. Skipped.");

            return;
        }

        File::put($path, $content);
        $relativePath = Str::replace(base_path() . '/', '', $path);
        $this->info("✅ {$type} created: {$relativePath}");
    }
}
