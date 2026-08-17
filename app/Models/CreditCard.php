<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditCard extends UserOwnedModel
{
    use HasFactory;

    protected $table = 'credit_cards';

    protected $fillable = ['name', 'issuer', 'last_digits', 'currency', 'limit_amount', 'available_limit', 'statement_day', 'due_day', 'status', 'metadata'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(CreditCardInvoice::class)->where('user_id', $this->user_id);
    }

    public function transactions()
    {
        return $this->hasMany(CreditCardTransaction::class)->where('user_id', $this->user_id);
    }
}
