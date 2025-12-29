<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Services;

use Adithwidhiantara\Crud\Http\Models\ModelCrud;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseCrudService
{
    abstract public function model(): ModelCrud;

    public function getAll(): Collection|LengthAwarePaginator
    {
        return $this->model()->query()
            ->get($this->model()->showOnList);
    }

    public function create(array $data): ModelCrud
    {
        return $this->model()->query()->create($data);
    }

    public function find(string|int $id): ModelCrud
    {
        return $this->model()->query()->findOrFail($id);
    }

    public function update(string|int $id, array $data): ModelCrud
    {
        $model = $this->find($id);

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|int $id): bool
    {
        $model = $this->find($id);

        return $model->delete();
    }
}
