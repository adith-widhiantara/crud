<?php

namespace Adithwidhiantara\Crud\Tests\Fixtures\Models;

use Adithwidhiantara\Crud\Http\Models\CrudModel;

class ChildModel extends CrudModel
{
    public function getShowOnListColumns(): array
    {
        return ['name'];
    }
}
