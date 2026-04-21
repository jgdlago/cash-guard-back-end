<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoriesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_system_and_user_categories(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Category::create([
            'user_id' => $user->id,
            'kind' => 'custom',
            'direction' => 'expense',
            'name' => 'Pets',
            'slug' => 'pets',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonFragment(['slug' => 'salario'])
            ->assertJsonFragment(['slug' => 'pets']);
    }
}
