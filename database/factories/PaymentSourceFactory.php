<?php

namespace Database\Factories;

use App\Enums\PaymentSourceType;
use App\Models\PaymentSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSource>
 */
class PaymentSourceFactory extends Factory
{
    protected $model = PaymentSource::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => PaymentSourceType::Wallet,
            'name' => fake()->company(),
            'currency_code' => 'BRL',
            'parent_payment_source_id' => null,
            'credit_limit_cents' => null,
            'statement_closing_day' => null,
            'statement_due_day' => null,
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function wallet(): static
    {
        return $this->state(fn (): array => [
            'type' => PaymentSourceType::Wallet,
            'credit_limit_cents' => null,
        ]);
    }

    public function bankAccount(): static
    {
        return $this->state(fn (): array => [
            'type' => PaymentSourceType::BankAccount,
            'credit_limit_cents' => null,
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (): array => [
            'type' => PaymentSourceType::CreditCard,
            'credit_limit_cents' => fake()->numberBetween(100000, 1200000),
            'statement_closing_day' => fake()->numberBetween(1, 28),
            'statement_due_day' => fake()->numberBetween(1, 28),
        ]);
    }
}
