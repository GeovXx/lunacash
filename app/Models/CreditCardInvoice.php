<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditCardInvoice extends UserOwnedModel
{
    use HasFactory;

    protected $table = 'credit_card_invoices';

    protected $fillable = ['credit_card_id', 'period_start', 'period_end', 'closing_date', 'due_date', 'status', 'minimum_amount', 'total_amount', 'paid_amount', 'metadata'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'closing_date' => 'date',
            'due_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function installments()
    {
        return $this->hasMany(CreditCardInstallment::class, 'invoice_id')->where('user_id', $this->user_id);
    }

    public function payments()
    {
        return $this->hasMany(CreditCardInvoicePayment::class, 'invoice_id')->where('user_id', $this->user_id);
    }
}
