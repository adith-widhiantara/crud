<?php

namespace Adithwidhiantara\Crud\Dtos;

use ReflectionClass;
use ReflectionException;

abstract readonly class BaseDto
{
    /**
     * Magic factory untuk memetakan array ke properti constructor child class.
     * Mengubah snake_case key di array menjadi camelCase argument di constructor.
     *
     * @throws ReflectionException
     */
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return new static;
        }

        $params = $constructor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $name = $param->getName(); // misal: typeName

            // 1. Coba cari match langsung (camelCase)
            if (array_key_exists($name, $data)) {
                $args[$name] = $data[$name];

                continue;
            }

            // 2. Coba cari match versi snake_case (type_name)
            $snakeName = self::camelToSnake($name);
            if (array_key_exists($snakeName, $data)) {
                $args[$name] = $data[$snakeName];

                continue;
            }

            // 3. Jika parameter punya default value di class, skip (biarkan default bekerja)
            if ($param->isDefaultValueAvailable()) {
                continue;
            }

            // 4. Jika nullable dan tidak ada di data, isi null
            if ($param->allowsNull()) {
                $args[$name] = null;

                continue;
            }

            // Opsional: Throw error jika field required hilang
            // throw new \InvalidArgumentException("Missing required field: $snakeName");
        }

        // Instansiasi class dengan argumen yang sudah dicocokkan
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Helper sederhana mengubah camelCase ke snake_case
     */
    private static function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
