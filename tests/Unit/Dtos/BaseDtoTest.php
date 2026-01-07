<?php

namespace Adithwidhiantara\Crud\Tests\Unit\Dtos;

use Adithwidhiantara\Crud\Dtos\BaseDto;
use Adithwidhiantara\Crud\Tests\TestCase;

class BaseDtoTest extends TestCase
{
    /** @test */
    public function it_can_map_snake_case_array_keys_to_camel_case_properties()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 30,
        ];

        $dto = TestPersonDto::fromArray($data);

        $this->assertEquals('John', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals(30, $dto->age);
    }

    /** @test */
    public function it_can_map_direct_property_names()
    {
        $data = [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'age' => 25,
        ];

        $dto = TestPersonDto::fromArray($data);

        $this->assertEquals('Jane', $dto->firstName);
        $this->assertEquals('Doe', $dto->lastName);
        $this->assertEquals(25, $dto->age);
    }

    /** @test */
    public function it_uses_default_values_when_missing_in_data()
    {
        $data = [
            'first_name' => 'Default',
        ];

        $dto = TestPersonDtoWithDefault::fromArray($data);

        $this->assertEquals('Default', $dto->firstName);
        $this->assertEquals('Unknown', $dto->lastName); // Default value
        $this->assertTrue($dto->isActive); // Default value
    }

    /** @test */
    public function it_handles_nullable_properties()
    {
        $data = [
            'first_name' => 'Nullable',
        ];

        $dto = TestPersonDtoNullable::fromArray($data);

        $this->assertEquals('Nullable', $dto->firstName);
        $this->assertNull($dto->lastName);
    }

    /** @test */
    public function it_returns_empty_instance_if_no_constructor()
    {
        $dto = TestEmptyDto::fromArray([]);
        $this->assertInstanceOf(TestEmptyDto::class, $dto);
    }
}

// -----------------------------------------------------------------------------
// Helper Classes for Testing
// -----------------------------------------------------------------------------

final readonly class TestPersonDto extends BaseDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public int $age
    ) {}
}

final readonly class TestPersonDtoWithDefault extends BaseDto
{
    public function __construct(
        public string $firstName,
        public string $lastName = 'Unknown',
        public bool $isActive = true
    ) {}
}

final readonly class TestPersonDtoNullable extends BaseDto
{
    public function __construct(
        public string $firstName,
        public ?string $lastName
    ) {}
}

final readonly class TestEmptyDto extends BaseDto {}
