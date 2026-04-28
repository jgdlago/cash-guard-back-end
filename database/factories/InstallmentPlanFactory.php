<?php

namespace Database\Factories;

use App\Models\InstallmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallmentPlan>
 */
class InstallmentPlanFactory extends Factory
{
    protected $model = InstallmentPlan::class;

    public function definition(): array
    {
        $installments = fake()->numberBetween(2, 12);
        $amount = fake()->numberBetween(50000, 500000);

        return [
            'user_id' => User::factory(),
            'payment_source_id' => null,
            'category_id' => null,
            'description' => fake()->sentence(3),
            'total_installments' => $installments,
            'total_amount_cents' => $amount,
            'currency_code' => 'BRL',
            'first_due_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ];
    }
}
