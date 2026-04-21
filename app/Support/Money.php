<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function parseToCents(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $normalized = preg_replace('/\s+/', '', $value);

        if (! is_string($normalized) || $normalized === '') {
            throw new InvalidArgumentException('Invalid monetary value.');
        }

        $negative = str_starts_with($normalized, '-');
        $absolute = ltrim($normalized, '-');

        if (str_contains($absolute, ',') && str_contains($absolute, '.')) {
            $absolute = str_replace('.', '', $absolute);
            $absolute = str_replace(',', '.', $absolute);
        } elseif (str_contains($absolute, ',')) {
            $absolute = str_replace(',', '.', $absolute);
        }

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $absolute)) {
            throw new InvalidArgumentException('Invalid monetary value.');
        }

        [$whole, $fraction] = array_pad(explode('.', $absolute, 2), 2, '0');
        $fraction = str_pad($fraction, 2, '0');

        $amount = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$amount : $amount;
    }

    public static function formatFromCents(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return number_format($amount / 100, 2, '.', '');
    }
}
