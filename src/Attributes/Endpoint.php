<?php

declare(strict_types=1);

namespace Adithwidhiantara\Crud\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Endpoint
{
    public const GET = 'GET';

    public const POST = 'POST';

    public const PUT = 'PUT';

    public const PATCH = 'PATCH';

    public const DELETE = 'DELETE';

    public const HEAD = 'HEAD';

    public const OPTIONS = 'OPTIONS';

    public const TRACE = 'TRACE';

    /**
     * @param  string|array  $method  HTTP Method (GET, POST, PUT, DELETE, PATCH, etc). Default 'GET'.
     * @param  string|null  $uri  Custom URI segment. Jika null, akan menggunakan kebab-case nama function.
     */
    public function __construct(
        public string|array $method = self::GET,
        public ?string $uri = null
    ) {
        //
    }
}
