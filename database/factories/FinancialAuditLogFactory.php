<?php

namespace Database\Factories;

use App\Models\FinancialAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAuditLog>
 */
class FinancialAuditLogFactory extends Factory
{
    protected $model = FinancialAuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'auditable_type' => fake()->randomElement(['transaction', 'recurring_rule', 'installment_plan']),
            'auditable_id' => fake()->numberBetween(1, 500),
            'event' => fake()->randomElement(['transaction.created', 'transaction.updated', 'recurring_rule.processed']),
            'before' => null,
            'after' => ['amount_cents' => fake()->numberBetween(-50000, 50000)],
            'context' => ['source' => fake()->randomElement(['api', 'console'])],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }
}
