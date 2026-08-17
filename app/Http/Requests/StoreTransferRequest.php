<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'uuid', 'different:to_account_id', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'to_account_id' => ['required', 'uuid', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'transfer_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled'])],
            'description' => ['nullable', 'string'],
        ];
    }
}
