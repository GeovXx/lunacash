<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditCardInstallment extends UserOwnedModel
{
    use HasFactory;

    protected $table = 'credit_card_installments';

    protected $fillable = ['credit_card_transaction_id', 'invoice_id', 'sequence', 'due_date', 'amount', 'currency', 'status', 'paid_at', 'metadata'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(CreditCardTransaction::class, 'credit_card_transaction_id');
    }

    public function invoice()
    {
        return $this->belongsTo(CreditCardInvoice::class, 'invoice_id');
    }
}
