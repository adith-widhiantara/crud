<?php

namespace Adithwidhiantara\Crud\Tests\Unit\Http\Services;

use Adithwidhiantara\Crud\Dtos\GetAllDto;
use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class BaseCrudServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->string('status')->nullable()->default('active');
            $table->timestamps();
        });

        Schema::create('related_models', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->timestamps();
        });

        Schema::table('test_models', function (Blueprint $table) {
            $table->foreignId('related_model_id')->nullable();
        });
    }

    private function createDto(
        int $perPage = 10,
        int $page = 1,
        bool $showAll = false,
        array $filter = [],
        ?string $search = null,
        ?string $sort = null
    ): GetAllDto {
        return new GetAllDto(
            perPage: $perPage,
            page: $page,
            showAll: $showAll,
            filter: $filter,
            search: $search,
            sort: $sort
        );
    }

    /** @test */
    public function get_all_returns_paginated_results()
    {
        ServiceTestModel::create(['name' => 'Item 1', 'price' => 100]);
        ServiceTestModel::create(['name' => 'Item 2', 'price' => 200]);

        $service = new ConcreteService;
        $dto = $this->createDto(perPage: 1, page: 1);

        $result = $service->getAll($dto);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('Item 1', $result->first()->name);
    }

    /** @test */
    public function get_all_returns_paginated_results_with_pagination_is_incorrect()
    {
        ServiceTestModel::create(['name' => 'Item 1', 'price' => 100]);
        ServiceTestModel::create(['name' => 'Item 2', 'price' => 200]);

        $service = new ConcreteService;
        $dto = $this->createDto(perPage: -1, page: -1);

        $result = $service->getAll($dto);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('Item 1', $result->first()->name);
    }

    /** @test */
    public function get_all_returns_paginated_results_with_pagination_perpage_more_than_100()
    {
        ServiceTestModel::create(['name' => 'Item 1', 'price' => 100]);
        ServiceTestModel::create(['name' => 'Item 2', 'price' => 200]);

        $service = new ConcreteService;
        $dto = $this->createDto(perPage: 120, page: -1);

        $result = $service->getAll($dto);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('Item 1', $result->first()->name);
    }

    /** @test */
    public function get_all_returns_collection_when_show_all_is_true()
    {
        ServiceTestModel::create(['name' => 'Item 1']);
        ServiceTestModel::create(['name' => 'Item 2']);

        $service = new ConcreteService;
        $dto = $this->createDto(showAll: true);

        $result = $service->getAll($dto);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function get_all_filters_by_equality()
    {
        ServiceTestModel::create(['name' => 'A', 'status' => 'active']);
        ServiceTestModel::create(['name' => 'B', 'status' => 'inactive']);

        $service = new ConcreteService;
        $dto = $this->createDto(filter: ['test_models.status' => 'active']);

        $result = $service->getAll($dto);

        $this->assertCount(1, $result);
        $this->assertEquals('A', $result->first()->name);
    }

    /** @test */
    public function get_all_filters_by_operator()
    {
        ServiceTestModel::create(['name' => 'Cheap', 'price' => 100]);
        ServiceTestModel::create(['name' => 'Expensive', 'price' => 1000]);

        $service = new ConcreteService;

        // Test gt
        $dto = $this->createDto(filter: ['price' => ['gt' => 500]]);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('Expensive', $result->first()->name);

        // Test lte
        $dto = $this->createDto(filter: ['price' => ['lte' => 100]]);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('Cheap', $result->first()->name);
    }

    /** @test */
    public function get_all_search_functionality()
    {
        ServiceTestModel::create(['name' => 'Apple']);
        ServiceTestModel::create(['name' => 'Banana']);

        $service = new ConcreteService;
        $dto = $this->createDto(search: 'App');

        $result = $service->getAll($dto);

        $this->assertCount(1, $result);
        $this->assertEquals('Apple', $result->first()->name);
    }

    /** @test */
    public function get_all_sorting()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 20]);

        $service = new ConcreteService;

        // Descending
        $dto = $this->createDto(sort: '-price');
        $result = $service->getAll($dto);
        $this->assertEquals('B', $result->first()->name);

        // Ascending
        $dto = $this->createDto(sort: 'price');
        $result = $service->getAll($dto);
        $this->assertEquals('A', $result->first()->name);
    }

    /** @test */
    public function create_stores_new_record()
    {
        $service = new ConcreteService;
        $data = ['name' => 'New Item', 'price' => 99, 'status' => 'active'];

        $model = $service->create($data);

        $this->assertDatabaseHas('test_models', ['name' => 'New Item']);
        $this->assertEquals('New Item', $model->name);
    }

    /** @test */
    public function update_modifies_record()
    {
        $item = ServiceTestModel::create(['name' => 'Old Name', 'price' => 10]);
        $service = new ConcreteService;

        $service->update($item->id, ['name' => 'New Name']);

        $this->assertDatabaseHas('test_models', ['id' => $item->id, 'name' => 'New Name']);
    }

    /** @test */
    public function delete_removes_record()
    {
        $item = ServiceTestModel::create(['name' => 'To Delete']);
        $service = new ConcreteService;

        $service->delete([$item->id]);

        $this->assertDatabaseMissing('test_models', ['id' => $item->id]);
    }

    /** @test */
    public function bulk_handle_manages_transactions()
    {
        $service = new ConcreteService;
        $item = ServiceTestModel::create(['name' => 'To Update']);
        $toDelete = ServiceTestModel::create(['name' => 'To Delete']);

        $data = [
            'create' => [
                ['name' => 'Bulk Created 1', 'price' => 10],
                ['name' => 'Bulk Created 2', 'price' => 20],
            ],
            'update' => [
                $item->id => ['name' => 'Updated Bulk'],
            ],
            'delete' => [
                $toDelete->id,
            ],
        ];

        $summary = $service->bulkHandle($data);

        $this->assertEquals(2, $summary['created']);
        $this->assertEquals(1, $summary['updated']);
        $this->assertEquals(1, $summary['deleted']);

        $this->assertDatabaseHas('test_models', ['name' => 'Bulk Created 1']);
        $this->assertDatabaseHas('test_models', ['name' => 'Updated Bulk']);
        $this->assertDatabaseMissing('test_models', ['id' => $toDelete->id]);
    }

    /** @test */
    public function it_throws_exception_if_wildcard_column_is_used()
    {
        $model = new class extends ServiceTestModel
        {
            public function getShowOnListColumns(): array
            {
                return ['test_models.*']; // Invalid
            }
        };

        // Mock service to use this model
        $service = new class($model) extends BaseCrudService
        {
            protected $model;

            public function __construct($model)
            {
                $this->model = $model;
            }

            public function model(): CrudModel
            {
                return $this->model;
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please define specific columns');

        $service->getAll($this->createDto());
    }

    /** @test */
    public function it_handles_null_filters()
    {
        ServiceTestModel::create(['name' => 'Null Status', 'status' => null]);
        ServiceTestModel::create(['name' => 'NotNull Status', 'status' => 'active']);

        $service = new ConcreteService;

        // Test is null
        $dto = $this->createDto(filter: ['test_models.status' => 'null']);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('Null Status', $result->first()->name);

        // Test not null
        $dto = $this->createDto(filter: ['test_models.status' => '!null']);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('NotNull Status', $result->first()->name);
    }

    /** @test */
    public function it_handles_between_operator()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 50]);
        ServiceTestModel::create(['name' => 'C', 'price' => 100]);

        $service = new ConcreteService;

        // String format "min,max"
        $dto = $this->createDto(filter: ['price' => ['between' => '20,80']]);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('B', $result->first()->name);

        // Array format [min, max]
        $dto = $this->createDto(filter: ['price' => ['between' => [90, 110]]]);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('C', $result->first()->name);
    }

    /** @test */
    public function it_handles_like_operator()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 50]);
        ServiceTestModel::create(['name' => 'C', 'price' => 100]);

        $service = new ConcreteService;

        // String format "like"
        $dto = $this->createDto(filter: ['price' => ['like' => 10]]);
        $result = $service->getAll($dto);
        $this->assertCount(2, $result);
        $this->assertEquals('A', $result->first()->name);
    }

    /** @test */
    public function it_handles_eq_operator()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 50]);
        ServiceTestModel::create(['name' => 'C', 'price' => 100]);

        $service = new ConcreteService;

        // String format "eq"
        $dto = $this->createDto(filter: ['price' => ['eq' => 10]]);
        $result = $service->getAll($dto);
        $this->assertCount(1, $result);
        $this->assertEquals('A', $result->first()->name);
    }

    /** @test */
    public function it_handles_gte_operator()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 50]);
        ServiceTestModel::create(['name' => 'C', 'price' => 100]);

        $service = new ConcreteService;

        // String format "gte"
        $dto = $this->createDto(filter: ['price' => ['gte' => 10]]);
        $result = $service->getAll($dto);
        $this->assertCount(3, $result);
        $this->assertEquals('A', $result->first()->name);
    }

    /** @test */
    public function it_handles_lt_operator()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 50]);
        ServiceTestModel::create(['name' => 'C', 'price' => 100]);

        $service = new ConcreteService;

        // String format "lt"
        $dto = $this->createDto(filter: ['price' => ['lt' => 10]]);
        $result = $service->getAll($dto);
        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_ignores_invalid_sort_columns()
    {
        ServiceTestModel::create(['name' => 'Z', 'price' => 10]);
        ServiceTestModel::create(['name' => 'A', 'price' => 20]);

        $service = new ConcreteService;

        $dto = $this->createDto(sort: 'name');
        $result = $service->getAll($dto);

        // Without effective sort, the order is usually insertion order (id) which defaults asc.
        // So Z (id 1) first, A (id 2) second.
        $this->assertCount(2, $result);
        $this->assertEquals('Z', $result->first()->name);
    }

    /** @test */
    public function get_all_search_with_nested_relation()
    {
        $related1 = RelatedModel::create(['description' => 'Apple']);
        ServiceTestModel::create(['name' => 'Item 1', 'related_model_id' => $related1->id]);

        $related2 = RelatedModel::create(['description' => 'Banana']);
        ServiceTestModel::create(['name' => 'Item 2', 'related_model_id' => $related2->id]);

        // Mock Service with nested search column
        $model = new class extends ServiceTestModel
        {
            public function searchableColumns(): array
            {
                return ['related.description'];
            }
        };

        $service = new class($model) extends BaseCrudService
        {
            protected $model;

            public function __construct($model)
            {
                $this->model = $model;
            }

            public function model(): CrudModel
            {
                return $this->model;
            }
        };

        $dto = $this->createDto(search: 'App');
        $result = $service->getAll($dto);

        $this->assertCount(1, $result);
        $this->assertEquals('Item 1', $result->first()->name);
    }

    /** @test */
    public function get_all_filters_by_list_of_values()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);
        ServiceTestModel::create(['name' => 'B', 'price' => 20]);
        ServiceTestModel::create(['name' => 'C', 'price' => 30]);

        $service = new ConcreteService;

        // filter by list: price IN (10, 30)
        $dto = $this->createDto(filter: ['price' => [10, 30]]);
        $result = $service->getAll($dto);

        $this->assertCount(2, $result);
        $this->assertEquals('A', $result->first()->name);
        $this->assertEquals('C', $result->last()->name);
    }

    /** @test */
    public function get_all_filters_ignores_invalid_operators()
    {
        ServiceTestModel::create(['name' => 'A', 'price' => 10]);

        $service = new ConcreteService;

        // filter with invalid operator should do nothing (or fail gracefully depending on query)
        // Here it basically adds nothing to query, so returns all
        $dto = $this->createDto(filter: ['price' => ['unknown_op' => 10]]);
        $result = $service->getAll($dto);

        $this->assertCount(1, $result);
        $this->assertEquals('A', $result->first()->name);
    }
}

class ServiceTestModel extends CrudModel
{
    protected $table = 'test_models';

    protected $guarded = [];

    public function getShowOnListColumns(): array
    {
        return ['id', 'name', 'price', 'status', 'related.description'];
    }

    public function filterableColumns(): array
    {
        return [$this->table.'.status', 'price', 'related.description'];
    }

    public function searchableColumns(): array
    {
        return ['name'];
    }

    public function sortableColumns(): array
    {
        return ['price'];
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class, 'related_model_id');
    }
}

class RelatedModel extends CrudModel
{
    protected $table = 'related_models';

    protected $guarded = [];

    public function getShowOnListColumns(): array
    {
        return ['id'];
    }
}

class ConcreteService extends BaseCrudService
{
    public function model(): CrudModel
    {
        return new ServiceTestModel;
    }
}
