<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        ActivityLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'action' => 'logout',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->getAuthIdentifier(),
            'description' => "{$event->user->name} keluar dari sistem",
            'ip_address' => request()?->ip(),
        ]);
    }
}
