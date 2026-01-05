<?php

namespace Adithwidhiantara\Crud\Tests\Feature;

use Adithwidhiantara\Crud\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MakeCrudCommandTest extends TestCase
{
    // Bersihkan file yang digenerate setelah test selesai
    protected function tearDown(): void
    {
        // Hapus file dummy yang mungkin terbuat
        $filesToDelete = [
            app_path('Models/Product.php'),
            app_path('Http/Controllers/ProductController.php'),
            app_path('Http/Services/ProductService.php'),
        ];

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_run_make_crud_command()
    {
        // 1. Jalankan command
        // Kita gunakan --no-interaction supaya tidak tanya-tanya input
        $this->artisan('make:crud', ['name' => 'Product'])
            ->assertExitCode(0); // 0 artinya sukses (Command::SUCCESS)

        // 2. Verifikasi File Terbuat
        // Cek Model
        $this->assertTrue(File::exists(app_path('Models/Product.php')), 'Model Product tidak ditemukan');

        // Cek Service
        $this->assertTrue(File::exists(app_path('Http/Services/ProductService.php')), 'Service Product tidak ditemukan');

        // Cek Controller
        $this->assertTrue(File::exists(app_path('Http/Controllers/ProductController.php')), 'Controller Product tidak ditemukan');
    }
}
