<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentPlansApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_variable_installments(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        $category = Category::query()->where('slug', 'cartao')->firstOrFail();
        $card = PaymentSource::create([
            'user_id' => $user->id,
            'type' => 'credit_card',
            'name' => 'Nubank',
            'currency_code' => 'BRL',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/installment-plans', [
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
}
