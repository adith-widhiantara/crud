<?php

namespace Adithwidhiantara\Crud\Tests\Feature;

use Adithwidhiantara\Crud\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeCrudCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupGeneratedFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();
        parent::tearDown();
    }

    protected function cleanupGeneratedFiles(): void
    {
        // Hapus file dummy yang mungkin terbuat
        $filesToDelete = [
            app_path('Models/Product.php'),
            app_path('Http/Controllers/ProductController.php'),
            app_path('Http/Services/ProductService.php'),
            base_path('tests/Feature/ProductControllerTest.php'),
            // Files for naming convention test
            app_path('Models/UserProfile.php'),
            app_path('Http/Controllers/UserProfileController.php'),
            app_path('Http/Services/UserProfileService.php'),
            base_path('tests/Feature/UserProfileControllerTest.php'),
        ];

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Cleanup migrations (using glob because filename has timestamp)
        $migrations = File::glob(database_path('migrations/*_create_products_table.php'));
        foreach ($migrations as $migration) {
            File::delete($migration);
        }

        $migrationsUserProfile = File::glob(database_path('migrations/*_create_user_profiles_table.php'));
        foreach ($migrationsUserProfile as $migration) {
            File::delete($migration);
        }
    }

    /** @test */
    public function it_can_run_make_crud_command()
    {
        // 1. Jalankan command
        $this->artisan('make:crud', ['name' => 'Product'])
            ->assertExitCode(0);

        // 2. Verifikasi File Terbuat
        // Cek Model
        $this->assertTrue(File::exists(app_path('Models/Product.php')), 'Model Product tidak ditemukan');

        // Cek Service
        $this->assertTrue(File::exists(app_path('Http/Services/ProductService.php')), 'Service Product tidak ditemukan');

        // Cek Controller
        $this->assertTrue(File::exists(app_path('Http/Controllers/ProductController.php')), 'Controller Product tidak ditemukan');

        // Cek Test
        $this->assertTrue(File::exists(base_path('tests/Feature/ProductControllerTest.php')), 'Test Product tidak ditemukan');

        // Cek Migration
        $migrations = File::glob(database_path('migrations/*_create_products_table.php'));
        $this->assertCount(1, $migrations, 'Migration file tidak ditemukan');
    }

    /** @test */
    public function it_generates_correct_file_content()
    {
        $this->artisan('make:crud', ['name' => 'Product']);

        // Verifikasi content Model
        $modelContent = File::get(app_path('Models/Product.php'));
        $this->assertStringContainsString('class Product extends CrudModel', $modelContent);
        $this->assertStringContainsString('namespace App\Models;', $modelContent);

        // Verifikasi content Service
        $serviceContent = File::get(app_path('Http/Services/ProductService.php'));
        $this->assertStringContainsString('class ProductService extends BaseCrudService', $serviceContent);
        $this->assertStringContainsString('return new Product();', $serviceContent);

        // Verifikasi content Controller
        $controllerContent = File::get(app_path('Http/Controllers/ProductController.php'));
        $this->assertStringContainsString('class ProductController extends BaseCrudController', $controllerContent);
        $this->assertStringContainsString('return new ProductService();', $controllerContent);
    }

    /** @test */
    public function it_skips_generation_if_file_exists()
    {
        // Run first time
        $this->artisan('make:crud', ['name' => 'Product']);

        // Capture original content
        $originalContent = File::get(app_path('Models/Product.php'));

        // Modify the file content to ensure it is NOT overwritten
        File::put(app_path('Models/Product.php'), 'MODIFIED CONTENT');

        // Run second time
        $this->artisan('make:crud', ['name' => 'Product'])
            ->expectsOutput('Model Product already exists. Skipped.')
            ->expectsOutput('Service ProductService already exists. Skipped.')
            ->expectsOutput('Controller ProductController already exists. Skipped.')
            ->expectsOutput('Unit Test ProductControllerTest already exists. Skipped.')
            ->assertExitCode(0);

        // Assert content is STILL the modified content (meaning it was skipped)
        $this->assertEquals('MODIFIED CONTENT', File::get(app_path('Models/Product.php')));
    }

    /** @test */
    public function it_handles_naming_conventions()
    {
        // Input snake_case atau lower case -> harus jadi PascalCase buat Class
        // user_profile -> UserProfile
        $this->artisan('make:crud', ['name' => 'user_profile'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Models/UserProfile.php')), 'Model UserProfile tidak terbuat');
        $this->assertTrue(File::exists(app_path('Http/Services/UserProfileService.php')), 'Service UserProfile tidak terbuat');

        // Cek migration name harus plural snake_case: user_profiles
        $migrations = File::glob(database_path('migrations/*_create_user_profiles_table.php'));
        $this->assertCount(1, $migrations, 'Migration user_profiles tidak ditemukan');
    }
    /** @test */
    public function it_creates_directories_if_they_do_not_exist()
    {
        // Simulate missing directories for the specific paths we want to test
        $dirsToCheck = [
            app_path('Models'),
            app_path('Http/Services'),
            app_path('Http/Controllers'),
            base_path('tests/Feature'),
        ];

        File::shouldReceive('exists')
            ->andReturnUsing(function ($path) use ($dirsToCheck) {
                // If it is one of our target directories, return false to trigger creation logic
                if (in_array($path, $dirsToCheck)) {
                    return false;
                }

                // For other entries (like verifying if generated file exists), allow checking real filesystem
                // or just return false so it proceeds to overwrite/create
                return file_exists($path);
            });

        // Expect makeDirectory calls
        File::shouldReceive('makeDirectory')->with(app_path('Models'), 0755, true)->once();
        File::shouldReceive('makeDirectory')->with(app_path('Http/Services'), 0755, true)->once();
        File::shouldReceive('makeDirectory')->with(app_path('Http/Controllers'), 0755, true)->once();
        File::shouldReceive('makeDirectory')->with(base_path('tests/Feature'), 0755, true)->once();

        // Expect put calls (file generation)
        File::shouldReceive('put')->andReturn(true);

        // Allow other standard File method calls that might occur (e.g. in tearDown)
        File::shouldReceive('delete')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
        File::shouldReceive('isDirectory')->andReturn(false);

        // Run command
        $this->artisan('make:crud', ['name' => 'NewDirTest'])
            ->assertExitCode(0);
    }
}
