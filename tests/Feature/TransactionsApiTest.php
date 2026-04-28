<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_expense_transaction_without_payment_source(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::query()->where('slug', 'alimentacao')->firstOrFail();

        $response = $this->postJson('/api/v1/transactions', [
            'type' => 'expense',
            'category_id' => $category->id,
            'amount' => '10,50',
            'transaction_date' => '2026-04-21',
            'description' => 'Almoço',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.amount_cents', -1050)
            ->assertJsonPath('data.payment_source_id', null);
    }

    public function test_it_creates_an_income_transaction_with_payment_source(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $paymentSource = PaymentSource::create([
            'user_id' => $user->id,
            'type' => 'wallet',
            'name' => 'Carteira',
            'currency_code' => 'BRL',
        ]);

        $response = $this->postJson('/api/v1/transactions', [
            'type' => 'income',
            'payment_source_id' => $paymentSource->id,
            'amount' => '250.00',
            'transaction_date' => '2026-04-21',
            'description' => 'Freela',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.amount_cents', 25000)
            ->assertJsonPath('data.payment_source_id', $paymentSource->id);
    }

    public function test_precognition_validates_transaction_without_creating_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withPrecognition()->postJson('/api/v1/transactions', [
            'type' => 'income',
            'amount' => '250.00',
            'transaction_date' => '2026-04-21',
            'description' => 'Freela',
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_rejects_unknown_fields_and_foreign_payment_sources(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $paymentSource = PaymentSource::create([
            'user_id' => $otherUser->id,
            'type' => 'wallet',
            'name' => 'Carteira',
            'currency_code' => 'BRL',
        ]);

        $response = $this->postJson('/api/v1/transactions', [
            'type' => 'income',
            'payment_source_id' => $paymentSource->id,
            'amount' => '250.00',
            'transaction_date' => '2026-04-21',
            'description' => 'Freela',
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_source_id', 'unexpected']);
    }

    public function test_it_cancels_a_transaction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $transaction = $user->transactions()->create([
            'type' => 'expense',
            'status' => 'posted',
            'amount_cents' => -1000,
            'currency_code' => 'BRL',
            'transaction_date' => '2026-04-21',
            'description' => 'Taxi',
            'posted_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/transactions/{$transaction->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_it_updates_transaction_and_writes_audit_log(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->expense(1000)->create([
            'user_id' => $user->id,
            'description' => 'Taxi',
        ]);

        $response = $this->patchJson("/api/v1/transactions/{$transaction->id}", [
            'type' => 'income',
            'amount' => '99,90',
            'transaction_date' => '2026-04-22',
            'description' => 'Reembolso taxi',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.amount_cents', 9990);

        $this->assertDatabaseHas('financial_audit_logs', [
            'user_id' => $user->id,
            'event' => 'transaction.updated',
        ]);
    }

    public function test_user_cannot_update_or_delete_foreign_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $transaction = Transaction::factory()->expense()->create(['user_id' => $otherUser->id]);

        $this->patchJson("/api/v1/transactions/{$transaction->id}", [
            'description' => 'Blocked',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/transactions/{$transaction->id}")
            ->assertNotFound();
    }

    public function test_dashboard_summarizes_current_user_month_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        Transaction::factory()->income(500000)->create([
            'user_id' => $user->id,
            'transaction_date' => '2026-04-01',
        ]);
        Transaction::factory()->expense(12500)->create([
            'user_id' => $user->id,
            'transaction_date' => '2026-04-03',
        ]);
        Transaction::factory()->expense(999999)->create([
            'user_id' => $otherUser->id,
            'transaction_date' => '2026-04-03',
        ]);
        Transaction::factory()->expense(5000)->cancelled()->create([
            'user_id' => $user->id,
            'transaction_date' => '2026-04-04',
        ]);

        $response = $this->getJson('/api/v1/dashboard?month=2026-04-01');

        $response->assertOk()
            ->assertJsonPath('data.summary.income_cents', 500000)
            ->assertJsonPath('data.summary.expense_cents', 12500)
            ->assertJsonPath('data.summary.balance_cents', 487500);
    }
}
