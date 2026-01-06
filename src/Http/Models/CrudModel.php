<?php

namespace Adithwidhiantara\Crud\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class CrudModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    abstract public function getShowOnListColumns(): array;

    public function ignoredColumns(array $ignoredColumns = []): array
    {
        if (method_exists($this, 'getDeletedAtColumn')) {
            $ignoredColumns[] = $this->getDeletedAtColumn();
        }

        return array_merge($ignoredColumns, [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ]);
    }

    /**
     * Tentukan kolom mana saja yang boleh difilter via request.
     * Format: ['status', 'category_id', 'author_id']
     */
    public function filterableColumns(): array
    {
        return [];
    }
}
