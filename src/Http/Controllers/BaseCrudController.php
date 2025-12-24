<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Spatie\RouteDiscovery\Attributes\Route;

abstract class BaseCrudController extends BaseController
{
    abstract protected function model(): Model;

    public function index(): JsonResponse
    {
        return Response::json($this->model()->all());
    }

    public function store(Request $request): JsonResponse
    {
        $createdData = $this->model()
            ->query()
            ->create($request->toArray());

        return Response::json([
            'message' => 'success',
            'data' => $createdData,
        ]);
    }

    #[Route(uri: '{id}')]
    public function show(string|int $id): JsonResponse
    {
        return Response::json($this->model()->query()->findOrFail($id));
    }

    #[Route(uri: '{id}')]
    public function update(Request $request, string|int $id): JsonResponse
    {
        $updatedData = $this->model()
            ->query()
            ->where('id', $id)
            ->update($request->toArray());

        return Response::json([
            'message' => 'success',
            'data' => $updatedData,
        ]);
    }

    #[Route(uri: '{id}')]
    public function destroy(string|int $id): JsonResponse
    {
        $this->model()
            ->query()
            ->where('id', $id)
            ->delete();

        return Response::json([
            'message' => 'success',
        ]);
    }
}
