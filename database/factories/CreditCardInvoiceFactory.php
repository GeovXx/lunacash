<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\CreditCard;
use App\Models\CreditCardInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCardInvoice>
 */
class CreditCardInvoiceFactory extends Factory
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
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'closing_date' => now(),
            'due_date' => now()->addDays(10),
            'status' => 'open',
            'minimum_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
        ];
    }
}
