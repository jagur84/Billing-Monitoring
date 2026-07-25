<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/**
 * Auto-records create/update/delete on the using model into activity_logs, so every write
 * to a business model is auditable without instrumenting each controller action by hand.
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));
    }

    protected function recordActivity(string $action): void
    {
        $properties = null;

        if ($action === 'updated') {
            $properties = $this->getChanges();
            unset($properties['updated_at']);

            foreach ($this->getHidden() as $hidden) {
                unset($properties[$hidden]);
            }

            if (empty($properties)) {
                return;
            }
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'description' => $this->activityDescription($action),
            'properties' => $properties,
            'ip_address' => request()?->ip(),
        ]);
    }

    protected function activityDescription(string $action): string
    {
        $verb = match ($action) {
            'created' => 'membuat',
            'updated' => 'memperbarui',
            'deleted' => 'menghapus',
            default => $action,
        };

        return "{$verb} ".class_basename(static::class)." {$this->activityLabel()}";
    }

    protected function activityLabel(): string
    {
        foreach (['name', 'invoice_number', 'ticket_number', 'sku', 'customer_code', 'email'] as $column) {
            if (! empty($this->{$column})) {
                return $this->{$column};
            }
        }

        return "#{$this->getKey()}";
    }
}
