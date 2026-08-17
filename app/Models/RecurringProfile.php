<?php

namespace App\Models;

class RecurringProfile extends UserOwnedModel
{
    protected $table = 'recurring_profiles';

    protected $fillable = ['account_id', 'category_id', 'type', 'name', 'frequency', 'amount', 'currency', 'next_occurrence_date', 'end_date', 'status', 'description', 'metadata'];

    protected function casts(): array
    {
        return [
            'next_occurrence_date' => 'date',
            'end_date' => 'date',
            'amount' => 'string',
            'metadata' => 'array',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
