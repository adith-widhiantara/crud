<?php

namespace Adithwidhiantara\Crud\Dtos;

final readonly class GetAllDto extends BaseDto
{
    public function __construct(
        public int $perPage,
        public int $page,
        public bool $showAll,
        public array $filter,
        public ?string $search,
        public ?string $sort,
    ) {
        //
    }
}
