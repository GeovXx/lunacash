<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\CreditCardInvoice;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\CreditCardInvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCardInvoicePayment>
 */
class CreditCardInvoicePaymentFactory extends Factory
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
            'invoice_id' => CreditCardInvoice::factory(),
            'account_id' => Account::factory(),
            'transaction_id' => Transaction::factory(),
            'amount' => 100,
            'currency' => 'BRL',
            'payment_date' => now(),
            'status' => 'completed',
        ];
    }
}
