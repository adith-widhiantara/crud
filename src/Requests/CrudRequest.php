<?php

namespace Adithwidhiantara\Crud\Requests;

use Adithwidhiantara\Crud\Contracts\StoreRequestContract;
use Adithwidhiantara\Crud\Contracts\UpdateRequestContract;
use Adithwidhiantara\Crud\Http\Controllers\BaseCrudController;
use Adithwidhiantara\Crud\Http\Models\CrudModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class CrudRequest extends FormRequest implements StoreRequestContract, UpdateRequestContract
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = $this->getModelFromController();

        $tableName = $model->getTable();
        $rules = [];

        $columns = Schema::getColumns($tableName);

        $ignoredColumns = $model->ignoredColumns();

        foreach ($columns as $column) {
            // Skip ignored columns
            if (in_array($column['name'], $ignoredColumns)) {
                continue;
            }

            $columnRules = [];

            // --- Rule: Nullable / Required ---
            // Jika kolom nullable, maka validasi 'nullable'.
            // Jika tidak nullable DAN tidak punya nilai default, maka 'required'.
            // (Jika punya default, kita boleh tidak kirim (nullable), nanti DB yang isi defaultnya)
            if ($column['nullable']) {
                $columnRules[] = 'nullable';
            } elseif ($column['default'] === null) {
                $columnRules[] = 'required';
            } else {
                // Not nullable tapi punya default (misal status='active'), opsional diisi
                $columnRules[] = 'nullable';
            }

            // --- Rule: Type Mapping ---
            $type = strtolower($column['type_name']);
            $fullType = strtolower($column['type']);      // Contoh: character varying(20)

            if (in_array($type, ['bool', 'boolean', 'tinyint(1)'])) {
                $columnRules[] = 'boolean';
            } elseif (str_contains($type, 'int')) {
                // integer, bigint, smallint, tinyint
                $columnRules[] = 'integer';
            } elseif (in_array($type, ['varchar', 'text', 'char', 'string'])) {
                $columnRules[] = 'string';

                if (preg_match('/\((\d+)\)/', $fullType, $matches)) {
                    $length = (int) $matches[1];
                    // Hanya tambahkan max jika length > 0
                    if ($length > 0) {
                        $columnRules[] = 'max:'.$length;
                    }
                }
            } elseif (in_array($type, ['decimal', 'float', 'double', 'numeric'])) {
                $columnRules[] = 'numeric';
            } elseif (in_array($type, ['date', 'time', 'datetime', 'timestamp'])) {
                $columnRules[] = 'date';
            } elseif (in_array($type, ['json'])) {
                $columnRules[] = 'array'; // Atau 'json'
            }

            // Assign ke array rules utama
            if (! empty($columnRules)) {
                $rules[$column['name']] = $columnRules;
            }
        }

        return $rules;
    }

    protected function getModelFromController(): CrudModel
    {
        $route = $this->route();

        /** @var BaseCrudController $controller */
        $controller = $route?->getController();

        return $controller->service()->model();
    }
}
