<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ========================================
// Scheduled Tasks Configuration
// ========================================

// Bill Lifecycle Management
Schedule::command('bills:close-expired')
    ->daily()
    ->at('00:00')
    ->onOneServer()
    ->emailOutputOnFailure(env('ADMIN_EMAIL'));

Schedule::command('bills:open-scheduled')
    ->daily()
    ->at('00:01')
    ->onOneServer()
    ->emailOutputOnFailure(env('ADMIN_EMAIL'));

// Analytics Updates
Schedule::command('analytics:update-clause-analytics')
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping(5);

// Cache Management
Schedule::command('cache:prune-stale-tags')
    ->hourly()
    ->onOneServer();

Schedule::command('cache:warm')
    ->everyThirtyMinutes()
    ->onOneServer()
    ->environments(['production', 'staging']);

// Database Maintenance
Schedule::command('db:backup')
    ->daily()
    ->at('02:00')
    ->onOneServer()
    ->emailOutputOnFailure(env('ADMIN_EMAIL'));

Schedule::command('queue:prune-failed --hours=48')
    ->daily()
    ->at('03:00')
    ->onOneServer();

// Cleanup Tasks
Schedule::command('model:prune')
    ->daily()
    ->at('04:00')
    ->onOneServer();

Schedule::command('sessions:prune')
    ->daily()
    ->at('04:30')
    ->onOneServer();

// Notification Cleanup
Schedule::command('notifications:cleanup')
    ->weekly()
    ->sundays()
    ->at('05:00')
    ->onOneServer();

// System Health Checks
Schedule::command('system:health-check')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->environments(['production']);

// Queue Monitoring
Schedule::command('queue:monitor redis:high,redis:default,redis:low --max=1000')
    ->everyMinute()
    ->onOneServer()
    ->environments(['production']);

// Log Rotation
Schedule::command('logs:rotate')
    ->daily()
    ->at('01:00')
    ->onOneServer();
