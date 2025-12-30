<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Services;

use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class BaseCrudService
{
    abstract public function model(): CrudModel;

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

    public function create(array $data): CrudModel
    {
        return $this->model()->query()->create($data);
    }

    public function find(string|int $id): CrudModel
    {
        return $this->model()->query()->findOrFail($id);
    }

    public function update(string|int $id, array $data): CrudModel
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

    /**
     * @throws Throwable
     */
    public function bulkHandle(array $data)
    {
        return DB::transaction(function () use ($data) {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
            ];

            if (! empty($data['create']) && is_array($data['create'])) {
                foreach ($data['create'] as $item) {
                    $this->create($item);
                    $summary['created']++;
                }
            }

            if (! empty($data['update']) && is_array($data['update'])) {
                foreach ($data['update'] as $id => $attributes) {
                    $this->update($id, $attributes);
                    $summary['updated']++;
                }
            }

            if (! empty($data['delete']) && is_array($data['delete'])) {
                // destroy() bisa menerima array ID sekaligus
                $deletedCount = $this->model()->destroy($data['delete']);
                $summary['deleted'] = $deletedCount;
            }

            return $summary;
        });
    }
}
