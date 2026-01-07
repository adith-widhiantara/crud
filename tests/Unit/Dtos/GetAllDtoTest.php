<?php

namespace Adithwidhiantara\Crud\Tests\Unit\Dtos;

use Adithwidhiantara\Crud\Dtos\GetAllDto;
use Adithwidhiantara\Crud\Tests\TestCase;

class GetAllDtoTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated_from_array()
    {
        $data = [
            'per_page' => 10,
            'page' => 1,
            'show_all' => false,
            'filter' => ['status' => 'active'],
            'search' => 'keyword',
            'sort' => 'created_at',
        ];

        $dto = GetAllDto::fromArray($data);

        $this->assertEquals(10, $dto->perPage);
        $this->assertEquals(1, $dto->page);
        $this->assertFalse($dto->showAll);
        $this->assertEquals(['status' => 'active'], $dto->filter);
        $this->assertEquals('keyword', $dto->search);
        $this->assertEquals('created_at', $dto->sort);
    }

    /** @test */
    public function it_handles_nullable_fields()
    {
        $data = [
            'per_page' => 20,
            'page' => 2,
            'show_all' => true,
            'filter' => [],
            // search and sort missing -> null
        ];

        $dto = GetAllDto::fromArray($data);

        $this->assertEquals(20, $dto->perPage);
        $this->assertEquals(2, $dto->page);
        $this->assertTrue($dto->showAll);
        $this->assertEquals([], $dto->filter);
        $this->assertNull($dto->search);
        $this->assertNull($dto->sort);
    }
}
