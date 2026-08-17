<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'type' => 'income',
            'amount' => 100,
            'currency' => 'BRL',
            'transaction_date' => now(),
            'status' => 'posted',
        ];
    }
}
