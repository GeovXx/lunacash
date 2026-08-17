<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends UserOwnedModel
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = ['account_id', 'category_id', 'financial_obligation_id', 'type', 'amount', 'currency', 'transaction_date', 'posted_at', 'status', 'reference', 'description', 'metadata', 'recurring_profile_id', 'recurring_occurrence_date'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'posted_at' => 'datetime', 'metadata' => 'array'];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function financialObligation()
    {
        return $this->belongsTo(FinancialObligation::class);
    }

    public function creditCardInvoicePayment()
    {
        return $this->hasOne(CreditCardInvoicePayment::class, 'transaction_id')->where('user_id', $this->user_id);
    }
}
