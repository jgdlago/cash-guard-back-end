<?php

namespace Database\Factories;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\RecurringRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringRule>
 */
class RecurringRuleFactory extends Factory
{
    protected $model = RecurringRule::class;

    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());
        $amount = fake()->numberBetween(1000, 300000);
        $startsOn = fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d');

        return [
            'user_id' => User::factory(),
            'payment_source_id' => null,
            'category_id' => null,
            'type' => $type,
            'frequency' => RecurringFrequency::Monthly,
            'status_on_generate' => TransactionStatus::Posted,
            'amount_cents' => $type === TransactionType::Expense ? -$amount : $amount,
            'currency_code' => 'BRL',
            'description' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'starts_on' => $startsOn,
            'next_run_on' => $startsOn,
            'ends_on' => null,
            'is_active' => true,
            'last_processed_at' => null,
        ];
    }

    public function income(int $amountCents = 500000): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Income,
            'amount_cents' => abs($amountCents),
        ]);
    }

    public function expense(int $amountCents = 5000): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Expense,
            'amount_cents' => -abs($amountCents),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'ends_on' => now()->toDateString(),
        ]);
    }
}
