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

    public function test_it_creates_custom_category_and_validates_payload(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Pets',
            'direction' => 'expense',
            'color' => '#22AACC',
            'icon' => 'paw',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.kind', 'custom')
            ->assertJsonPath('data.slug', 'pets');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'slug' => 'pets',
            'direction' => 'expense',
        ]);
    }

    public function test_category_rejects_foreign_parent_unknown_fields_and_invalid_color(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $foreignParent = Category::factory()->expense()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Invalid',
            'direction' => 'expense',
            'color' => 'red',
            'parent_id' => $foreignParent->id,
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['color', 'parent_id', 'unexpected']);
    }

    public function test_category_precognition_validates_without_creating(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withPrecognition()->postJson('/api/v1/categories', [
            'name' => 'Pets',
            'direction' => 'expense',
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertDatabaseMissing('categories', ['slug' => 'pets']);
    }
}
