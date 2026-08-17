<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountType extends Model
{
    use HasFactory;
    
    protected $table = 'account_types';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'name', 'nature'];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
        });
    }
}
