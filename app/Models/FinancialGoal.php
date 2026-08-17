<?php

namespace App\Models;

class FinancialGoal extends UserOwnedModel
{
    protected $table = 'financial_goals';

    protected $fillable = ['name', 'description', 'target_amount', 'currency', 'target_date', 'current_amount', 'status', 'metadata'];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function contributions()
    {
        return $this->hasMany(GoalContribution::class)->where('user_id', $this->user_id);
    }

    public function getProgressPercentageAttribute(): float
    {
        if (! $this->target_amount || $this->target_amount <= 0) {
            return 0;
        }

        $percentage = ($this->current_amount / $this->target_amount) * 100;

        return round(min(100, max(0, $percentage)), 2);
    }
}
