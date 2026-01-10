<?php

namespace Adithwidhiantara\Crud\Generators;

class CrudStub
{
    public static function model(string $name): string
    {
        return <<<PHP
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
    }

    public static function service(string $name): string
    {
        return <<<PHP
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
    }

    public static function controller(string $name): string
    {
        return <<<PHP
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
    }

    public static function unitTest(string $name, string $routeSlug, string $modelClass): string
    {
        return <<<PHP
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
    }
}
