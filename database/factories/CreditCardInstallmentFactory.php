<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\CreditCardTransaction;
use App\Models\CreditCardInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCardInstallment>
 */
class CreditCardInstallmentFactory extends Factory
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
            'credit_card_transaction_id' => CreditCardTransaction::factory(),
            'invoice_id' => CreditCardInvoice::factory(),
            'sequence' => 1,
            'amount' => 100,
            'currency' => 'BRL',
            'due_date' => now()->addDays(10),
            'status' => 'pending',
        ];
    }
}
