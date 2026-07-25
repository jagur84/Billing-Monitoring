<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate-upcoming')->dailyAt('07:00');
Schedule::command('invoices:mark-overdue')->dailyAt('01:30');
Schedule::command('notifications:send-reminders')->dailyAt('08:00');
Schedule::command('mikrotik:isolate-overdue')->dailyAt('02:00');
Schedule::command('mikrotik:restore-paid')->dailyAt('02:30');
