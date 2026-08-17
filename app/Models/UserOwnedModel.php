<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class UserOwnedModel extends Model
{
    use HasUserScope;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'user_id'];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();

            if (auth()->check()) {
                $model->user_id ??= auth()->id();
            }
        });
    }
}
