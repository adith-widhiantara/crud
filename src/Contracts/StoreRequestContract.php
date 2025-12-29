<?php

namespace Adithwidhiantara\Crud\Contracts;

interface StoreRequestContract
{
    public function all();

    public function validated();

    public function input(string $field, mixed $default = null);

    public function rules();

    public function merge(array $input);

    public function only(array $keys);
}
