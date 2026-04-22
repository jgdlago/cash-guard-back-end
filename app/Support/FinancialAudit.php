<?php

namespace App\Support;

use App\Models\FinancialAuditLog;
use Illuminate\Database\Eloquent\Model;

class FinancialAudit
{
    public static function log(
        ?int $userId,
        Model $auditable,
        string $event,
        ?array $before = null,
        ?array $after = null,
        array $context = []
    ): FinancialAuditLog {
        return FinancialAuditLog::create([
            'user_id' => $userId,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'before' => self::normalize($before),
            'after' => self::normalize($after),
            'context' => self::normalize($context),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public static function attributes(Model $model, array $onlyKeys = []): array
    {
        $attributes = $model->getAttributes();

        if ($onlyKeys !== []) {
            $attributes = array_intersect_key($attributes, array_flip($onlyKeys));
        }

        return self::normalize($attributes);
    }

    private static function normalize(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        array_walk_recursive($payload, function (&$value): void {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(DATE_ATOM);
            }
        });

        return $payload;
    }
}
