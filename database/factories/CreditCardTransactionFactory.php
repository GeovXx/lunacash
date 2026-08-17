<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\CreditCard;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCardTransaction>
 */
class CreditCardTransactionFactory extends Factory
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
            'credit_card_id' => CreditCard::factory(),
            'category_id' => Category::factory(),
            'description' => $this->faker->sentence(3),
            'amount' => 100,
            'currency' => 'BRL',
            'transaction_date' => now(),
            'status' => 'posted',
            'installments_total' => 1,
        ];
    }
}
