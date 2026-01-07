<?php

namespace Adithwidhiantara\Crud\Tests\Fixtures\Services;

use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Tests\Fixtures\Models\ChildModel;

class ChildService extends BaseCrudService
{
    public function model(): CrudModel
    {
        return new ChildModel;
    }
}
