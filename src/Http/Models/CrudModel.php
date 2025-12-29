<?php

namespace Adithwidhiantara\Crud\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class CrudModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    abstract public function getShowOnListColumns(): array;
}
