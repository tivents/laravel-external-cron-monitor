<?php

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Facades\Event;
use Tivents\CronMonitor\Listeners\CronListener;

describe('CronMonitorServiceProvider', function () {
    it('registriert Event Listener korrekt', function () {
        // Prüfe, dass die Event Listener registriert sind


        $listeners = Event::getListeners(ScheduledTaskFinished::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(ScheduledTaskFailed::class);
        expect($listeners)->not->toBeEmpty();
    });

    it('lädt Konfiguration korrekt', function () {
        expect(config('cron-monitor.central_log_url'))->not->toBeNull()
            ->and(config('cron-monitor.api_key'))->not->toBeNull();
    });

    it('CronListener ist als Singleton registriert', function () {
        $listener1 = app(CronListener::class);
        $listener2 = app(CronListener::class);

        expect($listener1)->toBe($listener2);
    });
});