<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\UserOwnedModel;

class AuditObserver
{
    public function created(UserOwnedModel $model): void
    {
        $this->write($model, 'created', $model->getAttributes());
    }

    public function updated(UserOwnedModel $model): void
    {
        $this->write($model, 'updated', $model->getChanges());
    }

    public function deleted(UserOwnedModel $model): void
    {
        $this->write($model, 'deleted', []);
    }

    private function write(UserOwnedModel $model, string $event, array $changes): void
    {
        if ($model instanceof AuditLog || $model->user_id === null) {
            return;
        }

        AuditLog::forceCreate([
            'user_id' => $model->user_id,
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'new_values' => $event === 'updated' ? $changes : null,
            'metadata' => ['source' => 'eloquent'],
        ]);
    }
}
