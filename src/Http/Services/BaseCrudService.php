<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Services;

use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

abstract class BaseCrudService
{
    abstract public function model(): CrudModel;

    public function getAll(int $perPage, int $page, bool $showAll): Collection|LengthAwarePaginator
    {
        $rawColumns = $this->model()->getShowOnListColumns();

        [$localColumns, $relations] = $this->parseColumnsAndRelations($rawColumns);

        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }
        if ($page < 1) {
            $page = 1;
        }

        $query = $this->model()->query();

        foreach ($relations as $relationName => $cols) {
            if (! in_array('id', $cols)) {
                $cols[] = 'id';
            }

            $query->with($relationName.':'.implode(',', $cols));
        }

        if ($showAll) {
            return $query->get($localColumns);
        }

        return $query->paginate(perPage: $perPage, columns: $localColumns, page: $page);
    }

    /**
     * @throws Throwable
     */
    protected function parseColumnsAndRelations(array $columns): array
    {
        $local = [];
        $relations = [];

        foreach ($columns as $column) {
            if (str_contains($column, '.')) {
                [$relation, $field] = explode('.', $column, 2);

                throw_if($field === '*', new InvalidArgumentException(
                    'Please define specific columns to avoid "SELECT *".'
                ));

                $relations[$relation][] = $field;
            } else {
                $local[] = $column;
            }
        }

        throw_if(empty($local), new InvalidArgumentException(
            'No local columns specified in getShowOnListColumns(). Please define specific columns to avoid "SELECT *".'
        ));

        return [$local, $relations];
    }

    public function beforeCreateHook(array $data): array
    {
        return $data;
    }

    public function afterCreateHook(CrudModel $model): CrudModel
    {
        return $model;
    }

    public function create(array $data): CrudModel
    {
        $finalData = $this->beforeCreateHook($data);

        /** @var CrudModel $result */
        $result = $this->model()->query()->create($finalData);

        return $this->afterCreateHook($result);
    }

    public function find(string|int $id): CrudModel
    {
        return $this->model()->query()->findOrFail($id);
    }

    public function beforeUpdateHook(array $data): array
    {
        return $data;
    }

    public function afterUpdateHook(CrudModel $model): CrudModel
    {
        return $model->refresh();
    }

    public function update(string|int $id, array $data): CrudModel
    {
        $model = $this->find($id);

        $finalData = $this->beforeUpdateHook($data);

        $model->update($finalData);

        return $this->afterUpdateHook($model);
    }

    public function beforeDeleteHook(array $ids): array
    {
        return $ids;
    }

    public function afterDeleteHook(int $ids): int
    {
        return $ids;
    }

    public function delete(array $ids): int
    {
        $finalData = $this->beforeDeleteHook($ids);

        return $this->afterDeleteHook($this->model()->destroy($finalData));
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
                $deletedCount = $this->delete($data['delete']);
                $summary['deleted'] = $deletedCount;
            }

            return $summary;
        });
    }
}
