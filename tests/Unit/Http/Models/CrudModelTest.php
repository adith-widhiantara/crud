<?php

namespace Adithwidhiantara\Crud\Tests\Unit\Http\Models;

use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchestra\Testbench\TestCase;

class CrudModelTest extends TestCase
{
    /** @test */
    public function it_returns_default_ignored_columns()
    {
        $model = new class extends CrudModel
        {
            public function getShowOnListColumns(): array
            {
                return [];
            }
        };

        $ignored = $model->ignoredColumns();

        $this->assertContains('id', $ignored);
        $this->assertContains('created_at', $ignored);
        $this->assertContains('updated_at', $ignored);
        $this->assertNotContains('deleted_at', $ignored);
    }

    /** @test */
    public function it_includes_deleted_at_if_soft_deletes_is_used()
    {
        $model = new class extends CrudModel
        {
            use SoftDeletes;

            public function getShowOnListColumns(): array
            {
                return [];
            }
        };

        $ignored = $model->ignoredColumns();

        $this->assertContains('deleted_at', $ignored);
    }

    /** @test */
    public function it_returns_empty_defaults_for_abstract_methods()
    {
        $model = new class extends CrudModel
        {
            public function getShowOnListColumns(): array
            {
                return [];
            }
        };

        $this->assertEquals([], $model->filterableColumns());
        $this->assertEquals([], $model->searchableColumns());
        $this->assertEquals(['created_at'], $model->sortableColumns());
    }
}
