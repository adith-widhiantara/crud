<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Controllers;

use Adithwidhiantara\Crud\Contracts\CrudControllerContract;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Spatie\RouteDiscovery\Attributes\Route;

abstract class BaseCrudController extends BaseController implements CrudControllerContract
{
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

    abstract protected function service(): BaseCrudService;

    public function store(Request $request): JsonResponse
    {
        return Response::json([
            'message' => 'success',
            'data' => $this->service()->create($request->toArray()),
        ]);
    }

    #[Route(uri: '{id}')]
    public function show(string|int $id): JsonResponse
    {
        return Response::json($this->service()->find($id));
    }

    #[Route(uri: '{id}')]
    public function update(Request $request, string|int $id): JsonResponse
    {
        return Response::json([
            'message' => 'success',
            'data' => $this->service()->update($id, $request->toArray()),
        ]);
    }

    #[Route(uri: '{id}')]
    public function destroy(string|int $id): JsonResponse
    {
        $this->service()->delete($id);

        return Response::json([
            'message' => 'success',
        ]);
    }
}
