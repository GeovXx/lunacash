<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))],
            'type' => ['required', Rule::in(['income', 'expense', 'adjustment', 'transfer', 'payment', 'refund'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'transaction_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['pending', 'posted', 'reconciled', 'cancelled'])],
            'reference' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
