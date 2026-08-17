<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCard>
 */
class CreditCardFactory extends Factory
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
            'name' => $this->faker->creditCardType,
            'issuer' => 'Bank',
            'last_digits' => $this->faker->numerify('####'),
            'limit_amount' => 5000,
            'available_limit' => 5000,
            'currency' => 'BRL',
            'statement_day' => 10,
            'due_day' => 20,
            'status' => 'active',
        ];
    }
}
