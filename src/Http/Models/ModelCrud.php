<?php

namespace Adithwidhiantara\Crud\Http\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ModelCrud extends Model
{
    protected $guarded = [];

    abstract public function getShowOnListColumns(): array;
}
