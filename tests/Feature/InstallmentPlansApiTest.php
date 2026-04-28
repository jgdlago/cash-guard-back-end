<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InstallmentPlan;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstallmentPlansApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_variable_installments(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::query()->where('slug', 'cartao')->firstOrFail();
        $card = PaymentSource::create([
            'user_id' => $user->id,
            'type' => 'credit_card',
            'name' => 'Nubank',
            'currency_code' => 'BRL',
        ]);

        $response = $this->postJson('/api/v1/installment-plans', [
            'payment_source_id' => $card->id,
            'category_id' => $category->id,
            'description' => 'Notebook',
            'transaction_date' => '2026-04-21',
            'installments' => [
                ['number' => 1, 'amount' => '1200.00', 'due_date' => '2026-05-10'],
                ['number' => 2, 'amount' => '950.00', 'due_date' => '2026-06-10'],
                ['number' => 3, 'amount' => '850.00', 'due_date' => '2026-07-10'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total_installments', 3)
            ->assertJsonPath('data.total_amount_cents', 300000)
            ->assertJsonCount(3, 'data.transactions')
            ->assertJsonPath('data.transactions.0.amount_cents', -120000);
    }

    public function test_it_rejects_non_sequential_installments_foreign_source_and_unknown_fields(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $foreignCard = PaymentSource::factory()->creditCard()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/v1/installment-plans', [
            'payment_source_id' => $foreignCard->id,
            'description' => 'Notebook',
            'transaction_date' => '2026-04-21',
            'installments' => [
                ['number' => 1, 'amount' => '1200.00', 'due_date' => '2026-05-10'],
                ['number' => 3, 'amount' => '950.00', 'due_date' => '2026-06-10'],
            ],
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_source_id', 'installments', 'unexpected']);
    }

    public function test_installment_precognition_does_not_create_plan_or_transactions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withPrecognition()->postJson('/api/v1/installment-plans', [
            'description' => 'Notebook',
            'transaction_date' => '2026-04-21',
            'installments' => [
                ['number' => 1, 'amount' => '1200.00', 'due_date' => '2026-05-10'],
                ['number' => 2, 'amount' => '950.00', 'due_date' => '2026-06-10'],
            ],
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertDatabaseCount('installment_plans', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_it_cancels_plan_transactions_and_logs_audit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = InstallmentPlan::factory()->create(['user_id' => $user->id]);
        Transaction::factory()->expense(5000)->pending()->create([
            'user_id' => $user->id,
            'installment_plan_id' => $plan->id,
            'installment_number' => 1,
            'total_installments' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/installment-plans/{$plan->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('transactions', [
            'installment_plan_id' => $plan->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('financial_audit_logs', [
            'user_id' => $user->id,
            'event' => 'installment_plan.cancelled',
        ]);
    }
}
