<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditCardInvoicePayment extends UserOwnedModel
{
    use HasFactory;

    protected $table = 'credit_card_invoice_payments';

    protected $fillable = ['invoice_id', 'account_id', 'transaction_id', 'amount', 'currency', 'payment_date', 'status', 'description', 'metadata'];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(CreditCardInvoice::class, 'invoice_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function bankTransaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
