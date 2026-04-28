<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());
        $amount = fake()->numberBetween(1000, 200000);

        return [
            'user_id' => User::factory(),
            'payment_source_id' => null,
            'category_id' => null,
            'installment_plan_id' => null,
            'recurring_rule_id' => null,
            'type' => $type,
            'status' => TransactionStatus::Posted,
            'amount_cents' => $type === TransactionType::Expense ? -$amount : $amount,
            'currency_code' => 'BRL',
            'transaction_date' => fake()->dateTimeBetween('-2 months', '+1 month')->format('Y-m-d'),
            'due_date' => null,
            'posted_at' => now(),
            'description' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'installment_number' => null,
            'total_installments' => null,
            'competence_month' => null,
            'cancelled_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    public function withSource(PaymentSource $source): static
    {
        return $this->state(fn (): array => [
            'payment_source_id' => $source->id,
            'user_id' => $source->user_id,
        ]);
    }

    public function withCategory(Category $category): static
    {
        return $this->state(fn (): array => [
            'category_id' => $category->id,
        ]);
    }

    public function income(int $amountCents = 100000): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Income,
            'amount_cents' => abs($amountCents),
        ]);
    }

    public function expense(int $amountCents = 10000): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Expense,
            'amount_cents' => -abs($amountCents),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => TransactionStatus::Pending,
            'posted_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => TransactionStatus::Cancelled,
            'posted_at' => null,
            'cancelled_at' => now(),
        ]);
    }
}
