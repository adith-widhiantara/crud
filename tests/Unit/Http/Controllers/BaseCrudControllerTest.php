<?php

namespace Adithwidhiantara\Crud\Tests\Unit\Http\Controllers;

use Adithwidhiantara\Crud\Dtos\GetAllDto;
use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Requests\CrudRequest;
use Adithwidhiantara\Crud\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;

class BaseCrudControllerTest extends TestCase
{
    protected $serviceMock;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceMock = Mockery::mock(BaseCrudService::class);

        $this->controller = new class ($this->serviceMock) extends BaseCrudController {
            protected $service;

            protected string $storeRequest = TestCrudRequest::class;

            protected string $updateRequest = TestCrudRequest::class;

            public function __construct($service)
            {
                parent::__construct();
                $this->service = $service;
            }

            public function service(): BaseCrudService
            {
                return $this->service;
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function get_route_key_name_returns_plural_kebab_case()
    {
        $this->serviceMock->shouldReceive('model')->andReturn(new ControllerTestModel);

        $controller = new class ($this->serviceMock) extends BaseCrudController {
            protected $service;

            public function __construct($service)
            {
                parent::__construct();
                $this->service = $service;
            }

            public function service(): BaseCrudService
            {
                return $this->service;
            }
        };

        $this->assertEquals('controller-test-models', $controller->getRouteKeyName());
    }

    /** @test */
    public function index_returns_paginated_json_response()
    {
        $request = Request::create('/?page=1&per_page=10', 'GET');

        $paginator = new LengthAwarePaginator(['item'], 1, 10);

        $this->serviceMock->shouldReceive('getAll')
            ->once()
            ->with(Mockery::type(GetAllDto::class))
            ->andReturn($paginator);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        // Paginator when json encoded becomes object, but we want to inspect structure
        // Verify 'data' key exists in original or data
        $data = $response->getData(true);
        $this->assertEquals(['item'], $data['data']);
        $this->assertEquals(10, $data['per_page']);
    }

    /** @test */
    public function index_returns_collection_json_response()
    {
        $request = Request::create('/?show_all=1', 'GET');
        $collection = new \Illuminate\Database\Eloquent\Collection(['item']);

        $this->serviceMock->shouldReceive('getAll')
            ->once()
            ->andReturn($collection);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertEquals(['item'], $data['data']);
    }

    /** @test */
    public function show_returns_json_response()
    {
        $id = 1;
        $model = new ControllerTestModel;

        $this->serviceMock->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($model);

        $response = $this->controller->show($id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($model->toArray(), $response->getData(true));
    }

    /** @test */
    public function destroy_returns_success_response()
    {
        $id = 1;

        $this->serviceMock->shouldReceive('delete')
            ->once()
            ->with([$id])
            ->andReturn(1);

        $response = $this->controller->destroy($id);

        $this->assertEquals(['message' => 'success'], (array) $response->getData(true));
    }

    /** @test */
    public function store_validates_and_creates()
    {
        $requestData = ['name' => 'Test'];

        $requestMock = Mockery::mock(CrudRequest::class);
        $requestMock->shouldReceive('validated')->once()->andReturn($requestData);

        $model = new ControllerTestModel;
        $this->serviceMock->shouldReceive('create')
            ->once()
            ->with($requestData)
            ->andReturn($model);

        $response = $this->controller->store($requestMock);

        $this->assertEquals('success', $response->getData(true)['message']);
        $this->assertEquals($model->toArray(), $response->getData(true)['data']);
    }

    /** @test */
    public function update_validates_and_updates()
    {
        $id = 1;
        $requestData = ['name' => 'Updated'];

        $requestMock = Mockery::mock(CrudRequest::class);
        $requestMock->shouldReceive('validated')->once()->andReturn($requestData);

        $model = new ControllerTestModel;
        $this->serviceMock->shouldReceive('update')
            ->once()
            ->with($id, $requestData)
            ->andReturn($model);

        $response = $this->controller->update($requestMock, $id);

        $this->assertEquals('success', $response->getData(true)['message']);
    }

    /** @test */
    public function bulk_handles_valid_data()
    {
        $data = [
            'create' => [['name' => 'A']],
            'delete' => ['1'],
        ];

        $request = Request::create('/bulk', 'POST', $data);

        $summary = ['created' => 1, 'deleted' => 1];

        $this->serviceMock->shouldReceive('bulkHandle')
            ->once()
            ->andReturn($summary);

        $response = $this->controller->bulk($request);

        $this->assertEquals('Bulk operation success', $response->getData(true)['message']);
        $this->assertEquals($summary, (array) $response->getData(true)['summary']);
    }

    /** @test */
    public function bulk_handles_non_existent_request_class()
    {
        $controller = new class ($this->serviceMock) extends BaseCrudController {
            protected $service;
            protected string $storeRequest = 'NonExistentClass'; // Class does not exist

            public function __construct($service)
            {
                parent::__construct();
                $this->service = $service;
            }

            public function service(): BaseCrudService
            {
                return $this->service;
            }
        };

        $request = Request::create('/bulk', 'POST', []);

        $this->serviceMock->shouldReceive('bulkHandle')->once()->andReturn([]);

        $response = $controller->bulk($request);

        $this->assertEquals('Bulk operation success', $response->getData(true)['message']);
    }

    /** @test */
    public function bulk_handles_request_class_without_rules_method()
    {
        $controller = new class ($this->serviceMock) extends BaseCrudController {
            protected $service;
            protected string $storeRequest = RequestWithoutRules::class;

            public function __construct($service)
            {
                parent::__construct();
                $this->service = $service;
            }

            public function service(): BaseCrudService
            {
                return $this->service;
            }
        };

        $request = Request::create('/bulk', 'POST', []);

        $this->serviceMock->shouldReceive('bulkHandle')->once()->andReturn([]);

        $response = $controller->bulk($request);

        $this->assertEquals('Bulk operation success', $response->getData(true)['message']);
    }

    /** @test */
    public function index_handles_invalid_filter_format()
    {
        // filter should be array, passing string
        $request = Request::create('/?filter=invalid_string', 'GET');

        // Service expect filter to be empty array if invalid
        $this->serviceMock->shouldReceive('getAll')
            ->once()
            ->with(Mockery::on(function ($dto) {
                return $dto->filter === [];
            }))
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([]));

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
    /** @test */
    public function bulk_executes_route_resolver_in_request()
    {
        $controller = new class ($this->serviceMock) extends BaseCrudController {
            protected $service;
            protected string $storeRequest = RequestWithRoute::class;

            public function __construct($service)
            {
                parent::__construct();
                $this->service = $service;
            }

            public function service(): BaseCrudService
            {
                return $this->service;
            }
        };

        // Mock route
        $route = (new \Illuminate\Routing\Route('POST', '/bulk', []))->bind(new Request);
        $request = Request::create('/bulk', 'POST', []);
        $request->setRouteResolver(fn() => $route);

        $this->serviceMock->shouldReceive('bulkHandle')->once()->andReturn([]);

        $response = $controller->bulk($request);

        $this->assertEquals('Bulk operation success', $response->getData(true)['message']);
    }
}

class TestCrudRequest extends CrudRequest
{
    public function rules(): array
    {
        return [];
    }
}

class RequestWithoutRules extends \Illuminate\Foundation\Http\FormRequest
{
    // No rules method
}

class RequestWithRoute extends \Illuminate\Foundation\Http\FormRequest
{
    public function rules(): array
    {
        // Accessing route should trigger the resolver closure
        $this->route();
        return [];
    }
}


class ControllerTestModel extends CrudModel
{
    public function getShowOnListColumns(): array
    {
        return ['id'];
    }
}
