<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Alimentação', 'type' => 'expense'],
            ['name' => 'Moradia', 'type' => 'expense'],
            ['name' => 'Transporte', 'type' => 'expense'],
            ['name' => 'Saúde', 'type' => 'expense'],
            ['name' => 'Educação', 'type' => 'expense'],
            ['name' => 'Lazer', 'type' => 'expense'],
            ['name' => 'Compras', 'type' => 'expense'],
            ['name' => 'Outras despesas', 'type' => 'expense'],
            ['name' => 'Salário', 'type' => 'income'],
            ['name' => 'Freelance', 'type' => 'income'],
            ['name' => 'Investimentos', 'type' => 'investment'],
            ['name' => 'Outras receitas', 'type' => 'income'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['user_id' => null, 'name' => $category['name']],
                ['type' => $category['type'], 'is_default' => true],
            );
        }
    }
}
