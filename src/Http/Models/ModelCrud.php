<?php

namespace Adithwidhiantara\Crud\Http\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ModelCrud extends Model
{
    protected $guarded = [];

    public array $showOnList = [];
}
