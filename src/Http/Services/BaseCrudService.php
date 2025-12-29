<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCrudService
{
    abstract public function model(): Model;

    public function getAll(): Collection|LengthAwarePaginator
    {
        return $this->model()->all();
    }

    public function create(array $data): Model
    {
        return $this->model()->query()->create($data);
    }

    public function find(string|int $id): Model
    {
        return $this->model()->query()->findOrFail($id);
    }

    public function update(string|int $id, array $data): Model
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
