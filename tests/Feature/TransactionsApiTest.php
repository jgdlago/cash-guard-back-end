<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentSource;
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
}
