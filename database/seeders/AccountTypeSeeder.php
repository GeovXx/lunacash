<?php

namespace Database\Seeders;

use App\Models\AccountType;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['key' => 'checking', 'name' => 'Conta Corrente', 'nature' => 'asset'],
            ['key' => 'savings', 'name' => 'Poupança', 'nature' => 'asset'],
            ['key' => 'wallet', 'name' => 'Carteira', 'nature' => 'asset'],
            ['key' => 'investment', 'name' => 'Investimentos', 'nature' => 'asset'],
        ];

        foreach ($types as $type) {
            AccountType::firstOrCreate(['key' => $type['key']], $type);
        }
    }
}
