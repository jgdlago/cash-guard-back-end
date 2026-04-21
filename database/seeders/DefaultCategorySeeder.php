<?php

namespace Database\Seeders;

use App\Enums\CategoryDirection;
use App\Enums\CategoryKind;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['direction' => CategoryDirection::Income, 'name' => 'Salário', 'slug' => 'salario', 'icon' => 'wallet', 'display_order' => 10],
            ['direction' => CategoryDirection::Income, 'name' => 'Freelas', 'slug' => 'freelas', 'icon' => 'briefcase', 'display_order' => 20],
            ['direction' => CategoryDirection::Income, 'name' => 'Investimentos', 'slug' => 'investimentos', 'icon' => 'chart-line', 'display_order' => 30],
            ['direction' => CategoryDirection::Income, 'name' => 'Reembolso', 'slug' => 'reembolso', 'icon' => 'receipt', 'display_order' => 40],
            ['direction' => CategoryDirection::Expense, 'name' => 'Moradia', 'slug' => 'moradia', 'icon' => 'house', 'display_order' => 50],
            ['direction' => CategoryDirection::Expense, 'name' => 'Alimentação', 'slug' => 'alimentacao', 'icon' => 'utensils', 'display_order' => 60],
            ['direction' => CategoryDirection::Expense, 'name' => 'Transporte', 'slug' => 'transporte', 'icon' => 'bus', 'display_order' => 70],
            ['direction' => CategoryDirection::Expense, 'name' => 'Saúde', 'slug' => 'saude', 'icon' => 'heart-pulse', 'display_order' => 80],
            ['direction' => CategoryDirection::Expense, 'name' => 'Educação', 'slug' => 'educacao', 'icon' => 'book-open', 'display_order' => 90],
            ['direction' => CategoryDirection::Expense, 'name' => 'Lazer', 'slug' => 'lazer', 'icon' => 'gamepad', 'display_order' => 100],
            ['direction' => CategoryDirection::Expense, 'name' => 'Assinaturas', 'slug' => 'assinaturas', 'icon' => 'repeat', 'display_order' => 110],
            ['direction' => CategoryDirection::Expense, 'name' => 'Impostos', 'slug' => 'impostos', 'icon' => 'landmark', 'display_order' => 120],
            ['direction' => CategoryDirection::Expense, 'name' => 'Cartão', 'slug' => 'cartao', 'icon' => 'credit-card', 'display_order' => 130],
            ['direction' => CategoryDirection::Expense, 'name' => 'Emergência', 'slug' => 'emergencia', 'icon' => 'shield-alert', 'display_order' => 140],
            ['direction' => CategoryDirection::Both, 'name' => 'Ajuste', 'slug' => 'ajuste', 'icon' => 'settings-2', 'display_order' => 150],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                [
                    'user_id' => null,
                    'slug' => $category['slug'],
                    'direction' => $category['direction']->value,
                ],
                [
                    'kind' => CategoryKind::System,
                    'name' => $category['name'],
                    'color' => null,
                    'icon' => $category['icon'],
                    'is_active' => true,
                    'display_order' => $category['display_order'],
                ]
            );
        }
    }
}
