<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditCardTransaction extends UserOwnedModel
{
    use HasFactory;

    protected $table = 'credit_card_transactions';

    protected $fillable = ['credit_card_id', 'category_id', 'description', 'amount', 'currency', 'transaction_date', 'status', 'installments_total', 'metadata'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function installments()
    {
        return $this->hasMany(CreditCardInstallment::class, 'credit_card_transaction_id')->where('user_id', $this->user_id);
    }
}
