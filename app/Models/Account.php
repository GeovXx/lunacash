<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends UserOwnedModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = ['account_type_id', 'name', 'institution', 'currency', 'account_number', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->where('user_id', $this->user_id);
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(Transfer::class, 'from_account_id')->where('user_id', $this->user_id);
    }

    public function incomingTransfers()
    {
        return $this->hasMany(Transfer::class, 'to_account_id')->where('user_id', $this->user_id);
    }

    public function goalContributions()
    {
        return $this->hasMany(GoalContribution::class)->where('user_id', $this->user_id);
    }

    public function getBalanceAttribute(): string
    {
        $sumAmounts = function ($query) {
            $total = '0.00';
            foreach ($query->toBase()->pluck('amount') as $amount) {
                $amtStr = is_string($amount) ? $amount : number_format($amount, 2, '.', '');
                $total = bcadd($total, $amtStr, 2);
            }

            return $total;
        };

        $incomes = $sumAmounts($this->transactions()->where('type', 'income'));
        $expenses = $sumAmounts($this->transactions()->whereIn('type', ['expense', 'payment']));
        $outgoingTransfers = $sumAmounts($this->outgoingTransfers());
        $incomingTransfers = $sumAmounts($this->incomingTransfers());
        $goalContributions = $sumAmounts($this->goalContributions());

        $totalIn = bcadd($incomes, $incomingTransfers, 2);
        $totalOut = bcadd($expenses, bcadd($outgoingTransfers, $goalContributions, 2), 2);

        return bcsub($totalIn, $totalOut, 2);
    }
}
