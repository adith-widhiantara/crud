<?php

namespace Adithwidhiantara\Crud\Tests\Unit\Requests;

use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Requests\CrudRequest;
use Adithwidhiantara\Crud\Tests\TestCase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;

class CrudRequestTest extends TestCase
{
    /** @test */
    public function it_authorizes_request()
    {
        $request = new CrudRequest;
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_generates_rules_based_on_schema()
    {
        // 1. Mock Schema Columns
        $tableName = 'users';
        $columns = [
            [
                'name' => 'id',
                'type_name' => 'bigint',
                'type' => 'bigint(20)',
                'nullable' => false,
                'default' => null,
                'auto_increment' => true,
            ],
            [
                'name' => 'name',
                'type_name' => 'varchar',
                'type' => 'varchar(255)',
                'nullable' => false,
                'default' => null,
            ],
            [
                'name' => 'email',
                'type_name' => 'string',
                'type' => 'string',
                'nullable' => true, // nullable
                'default' => null,
            ],
            [
                'name' => 'age',
                'type_name' => 'int',
                'type' => 'int',
                'nullable' => false,
                'default' => 18, // has default
            ],
            [
                'name' => 'bio',
                'type_name' => 'text', // string but no length limit in type
                'type' => 'text',
                'nullable' => true,
                'default' => null,
            ],
            [
                'name' => 'is_active',
                'type_name' => 'tinyint(1)', // boolean treatment
                'type' => 'tinyint(1)',
                'nullable' => false,
                'default' => 0,
            ],
            [
                'name' => 'salary',
                'type_name' => 'decimal',
                'type' => 'decimal(10,2)', // numeric
                'nullable' => false,
                'default' => null,
            ],
            [
                'name' => 'created_at',
                'type_name' => 'timestamp', // date
                'type' => 'timestamp',
                'nullable' => true,
                'default' => null,
            ],
            [
                'name' => 'metadata',
                'type_name' => 'json', // array
                'type' => 'json',
                'nullable' => true,
                'default' => null,
            ],
            [
                'name' => 'password', // ignored column
                'type_name' => 'varchar',
                'type' => 'varchar(255)',
                'nullable' => false,
                'default' => null,
            ],
        ];

        Schema::shouldReceive('getColumns')
            ->with($tableName)
            ->andReturn($columns);

        // 2. Mock Model
        $modelMock = Mockery::mock(CrudModel::class);
        $modelMock->shouldReceive('getTable')->andReturn($tableName);
        $modelMock->shouldReceive('ignoredColumns')->andReturn(['password']); // Should skip password

        // 3. Mock Service
        $serviceMock = Mockery::mock(BaseCrudService::class);
        $serviceMock->shouldReceive('model')->andReturn($modelMock);

        // 4. Mock Controller
        $controllerMock = Mockery::mock(BaseCrudController::class);
        $controllerMock->shouldReceive('service')->andReturn($serviceMock);

        // 5. Mock Route
        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getController')->andReturn($controllerMock);

        // 6. Setup Request
        $request = CrudRequest::create('/users', 'POST');
        $request->setRouteResolver(fn () => $routeMock);

        // Execute
        $rules = $request->rules();

        // Assertions

        // ID: ignored? No, code doesn't ignore ID by default unless in ignoredColumns.
        // But usually we don't validate ID on create?
        // The code logic:
        // if nullable -> nullable
        // elseif default === null -> required
        // else -> nullable

        // id: not nullable, default null -> required. type bigint -> integer
        $this->assertArrayHasKey('id', $rules);
        $this->assertContains('required', $rules['id']);
        $this->assertContains('integer', $rules['id']);

        // name: required, string, max:255
        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);

        // email: nullable, string
        $this->assertArrayHasKey('email', $rules);
        $this->assertContains('nullable', $rules['email']);
        $this->assertContains('string', $rules['email']);

        // age: has default -> nullable, integer
        $this->assertArrayHasKey('age', $rules);
        $this->assertContains('nullable', $rules['age']);
        $this->assertContains('integer', $rules['age']);

        // bio: nullable, string, text (no max)
        $this->assertArrayHasKey('bio', $rules);
        $this->assertContains('nullable', $rules['bio']);
        $this->assertContains('string', $rules['bio']);
        // Should NOT have max rule because 'text' type typically doesn't have (N) in type string unless specified like text(100)
        // preg_match('/\((\d+)\)/', 'text', ...) won't match.

        // is_active: has default -> nullable, boolean
        $this->assertArrayHasKey('is_active', $rules);
        $this->assertContains('nullable', $rules['is_active']);
        $this->assertContains('boolean', $rules['is_active']);

        // salary: required, numeric
        $this->assertArrayHasKey('salary', $rules);
        $this->assertContains('required', $rules['salary']);
        $this->assertContains('numeric', $rules['salary']);

        // created_at: nullable, date
        $this->assertArrayHasKey('created_at', $rules);
        $this->assertContains('nullable', $rules['created_at']);
        $this->assertContains('date', $rules['created_at']);

        // metadata: nullable, array
        $this->assertArrayHasKey('metadata', $rules);
        $this->assertContains('nullable', $rules['metadata']);
        $this->assertContains('array', $rules['metadata']);

        // password: ignored
        $this->assertArrayNotHasKey('password', $rules);
    }
}
