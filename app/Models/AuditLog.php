<?php

namespace App\Models;

class AuditLog extends UserOwnedModel
{
    protected $table = 'audit_logs';

    protected $fillable = ['event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent', 'metadata'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'metadata' => 'array'];
    }
}
