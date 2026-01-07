<?php

namespace Adithwidhiantara\Crud\Tests\Fixtures\Services;

use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Tests\Fixtures\Models\DummyModel;

class DummyService extends BaseCrudService
{
    public function model(): CrudModel
    {
        return new DummyModel;
    }
}
