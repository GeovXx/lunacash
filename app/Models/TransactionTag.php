<?php

namespace App\Models;

class TransactionTag extends UserOwnedModel
{
    protected $table = 'transaction_tags';

    protected $fillable = ['transaction_id', 'tag_id'];
}
