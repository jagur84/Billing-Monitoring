<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        ActivityLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'action' => 'login',
            'subject_type' => get_class($event->user),
            'subject_id' => $event->user->getAuthIdentifier(),
            'description' => "{$event->user->name} masuk ke sistem",
            'ip_address' => request()?->ip(),
        ]);
    }
}
