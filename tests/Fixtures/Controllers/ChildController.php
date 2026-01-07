<?php

namespace Adithwidhiantara\Crud\Tests\Fixtures\Controllers;

use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Tests\Fixtures\Services\ChildService;

class ChildController extends DummyController
{
    public function service(): BaseCrudService
    {
        return new ChildService;
    }
}
