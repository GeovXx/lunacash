<?php

namespace App\Models;

class Transfer extends UserOwnedModel
{
    protected $table = 'transfers';

    protected $fillable = ['from_account_id', 'to_account_id', 'amount', 'currency', 'transfer_date', 'status', 'description', 'metadata'];

    protected function casts(): array
    {
        return ['transfer_date' => 'date', 'metadata' => 'array'];
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
