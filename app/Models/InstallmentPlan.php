<?php

namespace App\Models;

class InstallmentPlan extends UserOwnedModel
{
    protected $table = 'installment_plans';

    protected $fillable = ['account_id',
        'category_id',
        'direction',
        'title',
        'total_amount',
        'installments_count',
        'first_due_date',
        'frequency',
        'status',
        'notes',
    ];

    protected $casts = [
        'first_due_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function obligations()
    {
        return $this->hasMany(FinancialObligation::class, 'installment_plan_id');
    }
}
