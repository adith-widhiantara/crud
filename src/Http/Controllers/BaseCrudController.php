<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Controllers;

use Adithwidhiantara\Crud\Attributes\Endpoint;
use Adithwidhiantara\Crud\Contracts\CrudControllerContract;
use Adithwidhiantara\Crud\Contracts\StoreRequestContract;
use Adithwidhiantara\Crud\Contracts\UpdateRequestContract;
use Adithwidhiantara\Crud\Http\Services\BaseCrudService;
use Adithwidhiantara\Crud\Requests\CrudRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 0);
        $showAll = (bool) $request->query('show_all', false);
        $search = $request->query('search');

        $filter = $request->query('filter', []);

        if (! is_array($filter)) {
            $filter = [];
        }

        $result = $this->service()->getAll(
            perPage: $perPage,
            page: $page,
            showAll: $showAll,
            filter: $filter,
            search: $search
        );

        if ($result instanceof Collection) {
            return Response::json([
                'data' => $result,
            ]);
        }

        return Response::json($result);
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
        $this->service()->delete([$id]);

        return Response::json([
            'message' => 'success',
        ]);
    }

    /**
     * @throws Throwable
     */
    #[Endpoint(method: Endpoint::POST, uri: 'bulk')]
    public function bulk(Request $request): JsonResponse
    {
        $bulkRules = [
            'create' => ['nullable', 'array'],
            'update' => ['nullable', 'array'],
            'delete' => ['nullable', 'array'],
            'delete.*' => ['required'],
        ];

        $storeRules = $this->getRulesFromRequest($this->storeRequest, $request);
        foreach ($storeRules as $field => $rules) {
            $bulkRules["create.*.$field"] = $rules;
        }

        $updateRules = $this->getRulesFromRequest($this->updateRequest, $request);
        foreach ($updateRules as $field => $rules) {
            $bulkRules["update.*.$field"] = $rules;
        }

        $validated = $request->validate($bulkRules);

        // Eksekusi Service
        $result = $this->service()->bulkHandle($validated);

        return Response::json([
            'message' => 'Bulk operation success',
            'summary' => $result,
        ]);
    }

    protected function getRulesFromRequest(string $requestClass, Request $currentRequest): array
    {
        if (! class_exists($requestClass)) {
            return [];
        }

        /** @var FormRequest $formRequest */
        // Kita instantiate manual kelas Request-nya
        $formRequest = new $requestClass;

        // [PENTING] Kita inject Route resolver dari request saat ini.
        // Ini agar logic '$this->route()' di dalam CrudRequest::rules() tetap jalan normal
        // dan bisa mendeteksi Controller/Model yang sedang aktif.
        $formRequest->setRouteResolver(fn () => $currentRequest->route());
        $formRequest->setContainer(app());

        if (method_exists($formRequest, 'rules')) {
            return $formRequest->rules();
        }

        return [];
    }
}
