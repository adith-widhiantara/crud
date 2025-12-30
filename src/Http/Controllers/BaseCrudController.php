<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Controllers;

use Adithwidhiantara\Crud\Contracts\CrudControllerContract;
use Adithwidhiantara\Crud\Contracts\StoreRequestContract;
use Adithwidhiantara\Crud\Contracts\UpdateRequestContract;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Requests\CrudRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

abstract class BaseCrudController extends BaseController implements CrudControllerContract
{
    protected string $storeRequest = CrudRequest::class;

    protected string $updateRequest = CrudRequest::class;

    public function __construct()
    {
        app()->bind(StoreRequestContract::class, $this->storeRequest);
        app()->bind(UpdateRequestContract::class, $this->updateRequest);
    }

    public function getRouteKeyName(): string
    {
        $model = $this->service()->model();

        $modelName = class_basename($model);

        return Str::plural(Str::kebab($modelName));
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 0);
        $showAll = (bool) $request->query('show_all', false);

        return Response::json($this->service()->getAll(perPage: $perPage, page: $page, showAll: $showAll));
    }

    abstract public function service(): BaseCrudService;

    public function store(StoreRequestContract $request): JsonResponse
    {
        /** @var CrudRequest $request */
        return Response::json([
            'message' => 'success',
            'data' => $this->service()->create($request->validated()),
        ]);
    }

    public function show(string|int $id): JsonResponse
    {
        return Response::json($this->service()->find($id));
    }

    public function update(UpdateRequestContract $request, string|int $id): JsonResponse
    {
        /** @var CrudRequest $request */
        return Response::json([
            'message' => 'success',
            'data' => $this->service()->update($id, $request->validated()),
        ]);
    }

    public function destroy(string|int $id): JsonResponse
    {
        $this->service()->delete($id);

        return Response::json([
            'message' => 'success',
        ]);
    }
}
