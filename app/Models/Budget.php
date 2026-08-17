<?php

namespace App\Models;

class Budget extends UserOwnedModel
{
    protected $table = 'budgets';

    protected $fillable = ['name', 'period_start', 'period_end', 'currency', 'target_amount', 'status', 'metadata'];

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class, 'budget_id');
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'metadata' => 'array',
        ];
    }
}
