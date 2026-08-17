<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasUserScope
{
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        return $query->where('user_id', $user?->id ?? auth()->id());
    }
}
