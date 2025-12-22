<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;

abstract class BaseCrudController extends BaseController
{
    abstract public function model(): Model;

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

    public function show(string|int $id): JsonResponse
    {
        return Response::json($this->model()->query()->findOrFail($id));
    }

    public function update(string|int $id, Request $request): JsonResponse
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