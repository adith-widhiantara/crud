<?php

namespace Adithwidhiantara\Crud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
        if (! File::exists(app_path('Models'))) {
            File::makeDirectory(app_path('Models'), 0755, true);
        }

        $path = app_path("Models/{$name}.php");

        if (File::exists($path)) {
            $this->warn("Model {$name} already exists. Skipped.");

            return;
        }

        // make factory
        $this->call('make:factory', [
            'name' => $name.'Factory',
            '--model' => $name,
        ]);

        $content = <<<PHP
<?php

namespace App\Models;

use Adithwidhiantara\Crud\Http\Models\CrudModel;

class {$name} extends CrudModel
{
    public function getShowOnListColumns(): array
    {
        return [];
    }
}
PHP;

        File::put($path, $content);
        $this->info("✅ Model & Factory created: app/Models/{$name}.php");
    }

    protected function generateService(string $name): void
    {
        // Pastikan direktori Services ada
        $directory = app_path('Http/Services');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = "{$directory}/{$name}Service.php";

        if (File::exists($path)) {
            $this->warn("Service {$name}Service already exists. Skipped.");

            return;
        }

        $content = <<<PHP
<?php

namespace App\Http\Services;

use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Http\Models\CrudModel;
use App\Models\\{$name};

class {$name}Service extends BaseCrudService
{
    public function model(): CrudModel
    {
        return new {$name}();
    }
}
PHP;

        File::put($path, $content);
        $this->info("✅ Service created: app/Http/Services/{$name}Service.php");
    }

    protected function generateController(string $name): void
    {
        // Pastikan direktori Controllers ada
        $directory = app_path('Http/Controllers');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = "{$directory}/{$name}Controller.php";

        if (File::exists($path)) {
            $this->warn("Controller {$name}Controller already exists. Skipped.");

            return;
        }

        $content = <<<PHP
<?php

namespace App\Http\Controllers;

use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use App\Http\Services\\{$name}Service;

class {$name}Controller extends BaseCrudController
{
    public function service(): BaseCrudService
    {
        return new {$name}Service();
    }
}
PHP;

        File::put($path, $content);
        $this->info("✅ Controller created: app/Http/Controllers/{$name}Controller.php");
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
        if (! File::exists($directory)) {
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
        $content = <<<PHP
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use {$modelClass};

class {$name}ControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string \$endpoint = '/api/{$routeSlug}';

    public function test_can_get_list_of_{$routeSlug}(): void
    {
        {$name}::factory()->count(3)->create();

        \$response = \$this->getJson(\$this->endpoint);

        \$response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_can_store_new_{$routeSlug}(): void
    {
        \$data = {$name}::factory()->make()->toArray();

        \$response = \$this->postJson(\$this->endpoint, \$data);

        \$response->assertStatus(200)
                 ->assertJson(['message' => 'success']);
        
        // Pastikan data masuk database
        \$this->assertDatabaseHas((new {$name})->getTable(), \$data);
    }

    public function test_can_show_detail_{$routeSlug}(): void
    {
        \$model = {$name}::factory()->create();

        \$response = \$this->getJson(\$this->endpoint . '/' . \$model->id);

        \$response->assertStatus(200)
                 ->assertJson(['id' => \$model->id]);
    }

    public function test_can_update_{$routeSlug}(): void
    {
        \$model = {$name}::factory()->create();
        \$newData = {$name}::factory()->make()->toArray();

        \$response = \$this->putJson(\$this->endpoint . '/' . \$model->id, \$newData);

        \$response->assertStatus(200)
                 ->assertJson(['message' => 'success']);

        \$this->assertDatabaseHas((new {$name})->getTable(), \$newData);
    }

    public function test_can_delete_{$routeSlug}(): void
    {
        \$model = {$name}::factory()->create();

        \$response = \$this->deleteJson(\$this->endpoint . '/' . \$model->id);

        \$response->assertStatus(200)
                 ->assertJson(['message' => 'success']);

        \$this->assertDatabaseMissing((new {$name})->getTable(), ['id' => \$model->id]);
    }
}
PHP;

        File::put($path, $content);
        $this->info("✅ Unit Test created: tests/Feature/{$name}ControllerTest.php");
    }
}
