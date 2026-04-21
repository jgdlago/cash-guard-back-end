<?php

namespace App\Casts;

use App\Support\Money;
use InvalidArgumentException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value)) {
            throw new InvalidArgumentException("Float is not a valid monetary input for [{$key}].");
        }

        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException("Unsupported monetary input for [{$key}].");
        }

        try {
            return Money::parseToCents($value);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException("Invalid monetary format for [{$key}].", previous: $exception);
        }
    }
}
