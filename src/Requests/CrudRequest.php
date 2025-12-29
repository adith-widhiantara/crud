<?php

namespace Adithwidhiantara\Crud\Requests;

use Adithwidhiantara\Crud\Contracts\StoreRequestContract;
use Adithwidhiantara\Crud\Contracts\UpdateRequestContract;
use Illuminate\Foundation\Http\FormRequest;

abstract class CrudRequest extends FormRequest implements StoreRequestContract, UpdateRequestContract
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
