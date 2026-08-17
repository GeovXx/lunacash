<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends UserOwnedModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = ['parent_id', 'name', 'type', 'is_default', 'metadata'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
        });
    }
}
