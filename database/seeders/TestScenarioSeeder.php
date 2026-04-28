<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\FinancialAuditLog;
use App\Models\InstallmentPlan;
use App\Models\PaymentSource;
use App\Models\RecurringRule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DefaultCategorySeeder::class);

        $user = User::factory()->create([
            'email' => 'scenario@example.com',
            'currency_code' => 'BRL',
        ]);

        $salary = Category::query()->where('slug', 'salario')->firstOrFail();
        $food = Category::query()->where('slug', 'alimentacao')->firstOrFail();
        $subscriptions = Category::query()->where('slug', 'assinaturas')->firstOrFail();
        $cardCategory = Category::query()->where('slug', 'cartao')->firstOrFail();

        $wallet = PaymentSource::factory()->wallet()->create([
            'user_id' => $user->id,
            'name' => 'Carteira principal',
            'display_order' => 1,
        ]);

        $card = PaymentSource::factory()->creditCard()->create([
            'user_id' => $user->id,
            'name' => 'Cartão teste',
            'display_order' => 2,
        ]);

        Transaction::factory()->income(500000)->withSource($wallet)->withCategory($salary)->create([
            'user_id' => $user->id,
            'transaction_date' => '2026-04-01',
            'description' => 'Salário',
        ]);

        Transaction::factory()->expense(8750)->withSource($wallet)->withCategory($food)->create([
            'user_id' => $user->id,
            'transaction_date' => '2026-04-05',
            'description' => 'Mercado',
        ]);

        $plan = InstallmentPlan::factory()->create([
            'user_id' => $user->id,
            'payment_source_id' => $card->id,
            'category_id' => $cardCategory->id,
            'description' => 'Notebook',
            'total_installments' => 2,
            'total_amount_cents' => 300000,
            'first_due_date' => '2026-05-10',
        ]);

        Transaction::factory()->expense(150000)->pending()->withSource($card)->withCategory($cardCategory)->create([
            'user_id' => $user->id,
            'installment_plan_id' => $plan->id,
            'transaction_date' => '2026-04-20',
            'due_date' => '2026-05-10',
            'description' => 'Notebook',
            'installment_number' => 1,
            'total_installments' => 2,
        ]);

        Transaction::factory()->expense(150000)->pending()->withSource($card)->withCategory($cardCategory)->create([
            'user_id' => $user->id,
            'installment_plan_id' => $plan->id,
            'transaction_date' => '2026-04-20',
            'due_date' => '2026-06-10',
            'description' => 'Notebook',
            'installment_number' => 2,
            'total_installments' => 2,
        ]);

        RecurringRule::factory()->expense(5990)->create([
            'user_id' => $user->id,
            'payment_source_id' => $card->id,
            'category_id' => $subscriptions->id,
            'description' => 'Streaming',
            'starts_on' => '2026-04-01',
            'next_run_on' => '2026-04-01',
        ]);

        FinancialAuditLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'scenario.seeded',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'after' => ['email' => $user->email],
            'context' => ['source' => 'seeder'],
        ]);
    }
}
