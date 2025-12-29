<?php

namespace Adithwidhiantara\Crud\Requests;

use Adithwidhiantara\Crud\Contracts\StoreRequestContract;
use Illuminate\Foundation\Http\FormRequest;

abstract class CrudRequest extends FormRequest implements StoreRequestContract, UpdateRequestContract
{
    public function rules(): array
    {
        return [];
    }
}
