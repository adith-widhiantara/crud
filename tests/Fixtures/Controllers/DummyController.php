<?php

namespace Adithwidhiantara\Crud\Tests\Fixtures\Controllers;

use Adithwidhiantara\Crud\Attributes\Endpoint;
use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Tests\Fixtures\Services\DummyService;
use Illuminate\Http\Request;

class DummyController extends BaseCrudController
{
    public function service(): BaseCrudService
    {
        return new DummyService;
    }

    #[Endpoint(method: Endpoint::GET, uri: 'custom-uri')]
    public function customMethod() {}

    // Should be automatically mapped to dummy-models/{id}/public-method
    public function publicMethod($id) {}

    // Should be automatically mapped to dummy-models/{id}/public-method-with-request
    public function publicMethodWithRequest(Request $request, $id) {}

    public function ignoredMethod() {}
}
