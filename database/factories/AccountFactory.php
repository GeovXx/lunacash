<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
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
            'account_type_id' => \App\Models\AccountType::factory(),
            'name' => $this->faker->word,
            'currency' => 'BRL',
            'status' => 'active',
        ];
    }
}
