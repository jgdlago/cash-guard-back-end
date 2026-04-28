<?php

namespace Database\Factories;

use App\Enums\CategoryDirection;
use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'kind' => CategoryKind::Custom,
            'direction' => fake()->randomElement(CategoryDirection::cases()),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['wallet', 'tag', 'calendar', 'card']),
            'parent_id' => null,
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 200),
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'kind' => CategoryKind::System,
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (): array => [
            'direction' => CategoryDirection::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (): array => [
            'direction' => CategoryDirection::Expense,
        ]);
    }

    public function both(): static
    {
        return $this->state(fn (): array => [
            'direction' => CategoryDirection::Both,
        ]);
    }
}
