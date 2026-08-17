<?php

namespace App\Models;

class GoalContribution extends UserOwnedModel
{
    protected $table = 'goal_contributions';

    protected $fillable = ['financial_goal_id', 'account_id', 'amount', 'currency', 'contribution_date', 'description', 'metadata'];

    protected function casts(): array
    {
        return [
            'contribution_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function financialGoal()
    {
        return $this->belongsTo(FinancialGoal::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
