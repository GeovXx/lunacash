<?php

namespace App\Models;

class Notification extends UserOwnedModel
{
    protected $table = 'notifications';

    protected $fillable = ['type', 'message', 'data', 'is_read', 'read_at', 'scheduled_for'];

    protected function casts(): array
    {
        return ['data' => 'array', 'is_read' => 'boolean', 'read_at' => 'datetime', 'scheduled_for' => 'datetime'];
    }
}
