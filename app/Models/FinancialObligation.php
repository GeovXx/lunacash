<?php

namespace App\Models;

class FinancialObligation extends UserOwnedModel
{
    protected $table = 'financial_obligations';

    protected $fillable = ['account_id',
        'category_id',
        'installment_plan_id',
        'installment_number',
        'direction',
        'title',
        'amount',
        'currency',
        'due_date',
        'issued_date',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'issued_date' => 'date',
        'metadata' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function installmentPlan()
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'open' && $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
