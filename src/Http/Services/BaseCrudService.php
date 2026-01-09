<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Http\Services;

use Adithwidhiantara\Crud\Dtos\GetAllDto;
use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

abstract class BaseCrudService
{
    abstract public function model(): CrudModel;

    /**
     * @throws Throwable
     */
    public function getAll(GetAllDto $data): Collection|LengthAwarePaginator
    {
        $perPage = $data->perPage;
        $page = $data->page;
        $search = $data->search;

        $model = $this->model();
        $tableName = $model->getTable();

        $rawColumns = $model->getShowOnListColumns();

        [$localColumns, $relations] = $this->parseColumnsAndRelations(array_merge([$tableName.'.id'], array_map(function (string $column) use ($tableName) {
            return Str::contains($column, '.') ? $column : $tableName.'.'.$column;
        }, $rawColumns)));

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
            if (!in_array('id', $cols)) {
                $cols[] = 'id';
            }

            $query->with($relationName . ':' . implode(',', $cols));
        }

        $this->applyFilters($query, $data->filter);

        if ($search) {
            $this->applySearch($query, $search);
        }

        $this->applySorting($query, $data->sort);

        $query = $this->extendQuery($query);

        if ($data->showAll) {
            return $query->get($localColumns);
        }

        return $query->paginate(perPage: $perPage, columns: $localColumns, page: $page);
    }

    /**
     * Logic Sorting Dynamic
     * Format: ?sort=price (ASC) atau ?sort=-price (DESC)
     */
    protected function applySorting(Builder $query, ?string $sort): void
    {
        if (!$sort) {
            return;
        }

        $direction = 'asc';

        // Cek prefix '-' for descending
        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $sort = substr($sort, 1);
        }

        // Validate Whitelist from Model
        if (in_array($sort, $this->model()->sortableColumns())) {
            $query->orderBy($sort, $direction);
        }
    }

    /**
     * Implement search logic that supports nested relationships via dot notation.
     * Example: 'title', 'category.name', 'posts.comments.body'
     */
    protected function applySearch(Builder $query, string $search): void
    {
        $columns = $this->model()->searchableColumns();

        if (empty($columns)) {
            return;
        }

        $query->where(function (Builder $q) use ($columns, $search) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    $lastDotPosition = strrpos($column, '.');
                    $relation = substr($column, 0, $lastDotPosition);
                    $field = substr($column, $lastDotPosition + 1);
                    $q->orWhereRelation($relation, $field, 'LIKE', "%{$search}%");
                } else {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Logic Filter with support Operator (Range, Like, Between)
     */
    private function applyFilters(Builder $query, array $filter): void
    {
        $allowedFilters = $this->model()->filterableColumns();

        foreach ($filter as $column => $value) {
            if (!in_array($column, $allowedFilters)) {
                continue;
            }

            $qualifiedColumn = str_contains($column, '.')
                ? $column
                : $this->model()->getTable() . '.' . $column;

            // Handle Null Checks (Existing)
            if ($value === 'null') {
                $query->whereNull($qualifiedColumn);

                continue;
            }

            if ($value === '!null') {
                $query->whereNotNull($qualifiedColumn);

                continue;
            }

            // Handle Array Values (Operator vs WhereIn)
            if (is_array($value)) {
                // Check if array associative (Key-Value) -> Operator Logic
                // Example: filter[price][gte]=1000 -> ['gte' => 1000]
                if (Arr::isAssoc($value)) {
                    foreach ($value as $operator => $val) {
                        $this->applyOperator($query, $qualifiedColumn, $operator, $val);
                    }
                } else {
                    // Normal array (Indexed) -> WhereIn Logic
                    // Example: filter[category_id][]=1&filter[category_id][]=2 -> [1, 2]
                    $query->whereIn($qualifiedColumn, $value);
                }
            } else {
                // Basic Equality (Existing)
                $query->where($qualifiedColumn, $value);
            }
        }
    }

    /**
     * Helper for translating operator string to Query Builder
     */
    protected function applyOperator(Builder $query, string $column, string $operator, mixed $value): void
    {
        match ($operator) {
            'eq' => $query->where($column, '=', $value),
            'gt' => $query->where($column, '>', $value),
            'gte' => $query->where($column, '>=', $value),
            'lt' => $query->where($column, '<', $value),
            'lte' => $query->where($column, '<=', $value),
            'like' => $query->where($column, 'LIKE', "%{$value}%"),
            'between' => $this->handleBetweenOperator($query, $column, $value),
            default => null, // Ignore when operator not registered
        };
    }

    protected function handleBetweenOperator(Builder $query, string $column, mixed $value): void
    {
        // Support format string comma-separated: filter[date][between]=2023-01-01,2023-01-31
        if (is_string($value) && str_contains($value, ',')) {
            $value = explode(',', $value);
        }

        // Support format array: filter[date][between][]=2023-01-01&filter[date][between][]=2023-01-31
        if (is_array($value) && count($value) >= 2) {
            $query->whereBetween($column, [$value[0], $value[1]]);
        }
    }

    protected function extendQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @throws Throwable
     */
    protected function parseColumnsAndRelations(array $columns): array
    {
        $model = $this->model();
        $tableName = $model->getTable();

        $local = [];
        $relations = [];

        foreach ($columns as $column) {
            $strBefore = Str::before($column, '.');
            if ($strBefore !== $tableName) {
                [$relation, $field] = explode('.', $column, 2);

                throw_if($field === '*', new InvalidArgumentException(
                    'Please define specific columns to avoid "SELECT *".'
                ));

                $relations[$relation][] = $field;
            } else {
                throw_if(Str::endsWith($column, '.*'), new InvalidArgumentException(
                    'Please define specific columns to avoid "SELECT *".'
                ));

                $local[] = $column;
            }
        }

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

            if (!empty($data['create']) && is_array($data['create'])) {
                foreach ($data['create'] as $item) {
                    $this->create($item);
                    $summary['created']++;
                }
            }

            if (!empty($data['update']) && is_array($data['update'])) {
                foreach ($data['update'] as $id => $attributes) {
                    $this->update($id, $attributes);
                    $summary['updated']++;
                }
            }

            if (!empty($data['delete']) && is_array($data['delete'])) {
                // destroy() bisa menerima array ID sekaligus
                $deletedCount = $this->delete($data['delete']);
                $summary['deleted'] = $deletedCount;
            }

            return $summary;
        });
    }
}
