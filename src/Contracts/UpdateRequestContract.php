<?php

namespace Adithwidhiantara\Crud\Contracts;

interface UpdateRequestContract
{
    public function all();

    public function validated();

    public function input(string $field);

    public function rules();

    public function authorize();
}
