<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Services;

use Adithwidhiantara\Crud\Http\Models\ModelCrud;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseCrudService
{
    abstract public function model(): ModelCrud;

    public function getAll(int $perPage, int $page, bool $showAll): Collection|LengthAwarePaginator
    {
        $columns = $this->model()->getShowOnListColumns();

        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }
        if ($page < 1) {
            $page = 1;
        }

        if ($showAll) {
            return $this->model()->query()->get($columns);
        }

        return $this->model()->query()
            ->paginate(perPage: $perPage, columns: $columns, page: $page);
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
