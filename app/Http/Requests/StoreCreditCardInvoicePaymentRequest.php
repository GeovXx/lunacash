<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreditCardInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'uuid', Rule::exists('credit_card_invoices', 'id')->where('user_id', auth()->id())],
            'account_id' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'transaction_id' => ['required', 'uuid', Rule::exists('transactions', 'id')->where('user_id', auth()->id())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['pending', 'completed', 'failed', 'cancelled'])],
            'description' => ['nullable', 'string'],
        ];
    }
}
