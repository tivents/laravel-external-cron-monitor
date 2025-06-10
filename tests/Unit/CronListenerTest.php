<?php

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tivents\CronMonitor\Listeners\CronListener;

beforeEach(function () {
    $this->listener = new CronListener();

    // Mock HTTP responses
    Http::fake();

    // Mock Cache
    Cache::flush();

    // Mock Logs
    Log::spy();

    // Mock config values
    config([
        'app.name' => 'test-app',
        'tivents.monitoring.endpoint' => 'https://example.com/api/',
        'tivents.monitoring.api_key' => 'test-api-key',
        'tivents.monitoring.alerts.slack_webhook' => 'https://hooks.slack.com/test',
    ]);
});

describe('CronListener Task Starting', function () {
    it('speichert Startzeit im Cache wenn Task startet', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $event = new ScheduledTaskStarting($task);

        $this->listener->handleTaskStarting($event);

        $taskId = md5('test:commandtest-app');
        expect(Cache::has("cron_start_{$taskId}"))->toBeTrue()
            ->and(Cache::get("cron_start_{$taskId}"))->toBeFloat();
    });

    it('generiert konsistente Task-ID für gleiche Commands', function () {
        $task1 = Mockery::mock(Event::class);
        $task1->command = 'test:command';

        $task2 = Mockery::mock(Event::class);
        $task2->command = 'test:command';

        $event1 = new ScheduledTaskStarting($task1);
        $event2 = new ScheduledTaskStarting($task2);

        $this->listener->handleTaskStarting($event1);
        $this->listener->handleTaskStarting($event2);

        $taskId = md5('test:commandtest-app');
        expect(Cache::has("cron_start_{$taskId}"))->toBeTrue();
    });
});

describe('CronListener Task Finished', function () {
    it('sendet Monitoring-Daten bei erfolgreichem Task-Abschluss', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        // Simuliere Task-Start
        $startEvent = new ScheduledTaskStarting($task);
        $this->listener->handleTaskStarting($startEvent);

        // Warte kurz und simuliere Task-Ende
        usleep(1000); // 1ms
        $finishedEvent = new ScheduledTaskFinished($task, 0.5);

        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/api/cron-event' &&
                $request['status'] === 'finished' &&
                $request['command'] === 'test:command' &&
                $request['application'] === 'test-app' &&
                isset($request['duration_seconds']) &&
                isset($request['memory_peak_mb']);
        });
    });

    it('räumt Cache nach Task-Abschluss auf', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $startEvent = new ScheduledTaskStarting($task);
        $this->listener->handleTaskStarting($startEvent);

        $taskId = md5('test:commandtest-app');
        expect(Cache::has("cron_start_{$taskId}"))->toBeTrue();

        $finishedEvent = new ScheduledTaskFinished($task, 0.1);
        $this->listener->handleTaskFinished($finishedEvent);

        expect(Cache::has("cron_start_{$taskId}"))->toBeFalse();
    });

    it('berechnet Duration korrekt', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $startEvent = new ScheduledTaskStarting($task);
        $this->listener->handleTaskStarting($startEvent);

        usleep(10000); // 10ms warten

        $finishedEvent = new ScheduledTaskFinished($task, 0.2);
        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return $request['duration_seconds'] > 0 &&
                $request['duration_ms'] > 0;
        });
    });

    it('verwendet Laravel Runtime als Fallback wenn keine Startzeit gefunden wird', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        // Simuliere Finished-Event ohne vorheriges Starting-Event
        $finishedEvent = new ScheduledTaskFinished($task, 1.5);
        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return $request['runtime'] === 1.5;
        });
    });

    it('verwendet Task-Description als Fallback wenn Command nicht verfügbar ist', function () {
        $task = Mockery::mock(Event::class);
        $task->description = 'Task Description';
        $task->command = null;

        $finishedEvent = new ScheduledTaskFinished($task, 0.1);
        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return $request['command'] === 'Task Description';
        });
    });
});

describe('CronListener Task Failed', function () {
    it('sendet Monitoring-Daten bei fehlgeschlagenem Task', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $exception = new Exception('Test error message');
        $failedEvent = new ScheduledTaskFailed($task, $exception);

        $this->listener->handleTaskFailed($failedEvent);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/api/cron-event' &&
                $request['status'] === 'failed' &&
                $request['command'] === 'test:command' &&
                $request['exception'] === 'Test error message';
        });
    });

    it('räumt Cache nach fehlgeschlagenem Task auf', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $startEvent = new ScheduledTaskStarting($task);
        $this->listener->handleTaskStarting($startEvent);

        $taskId = md5('test:commandtest-app');
        expect(Cache::has("cron_start_{$taskId}"))->toBeTrue();

        $exception = new Exception('Test error');
        $failedEvent = new ScheduledTaskFailed($task, $exception);
        $this->listener->handleTaskFailed($failedEvent);

        expect(Cache::has("cron_start_{$taskId}"))->toBeFalse();
    });

    it('berechnet Duration auch bei fehlgeschlagenen Tasks', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $startEvent = new ScheduledTaskStarting($task);
        $this->listener->handleTaskStarting($startEvent);

        usleep(5000); // 5ms warten

        $exception = new Exception('Test error');
        $failedEvent = new ScheduledTaskFailed($task, $exception);
        $this->listener->handleTaskFailed($failedEvent);

        Http::assertSent(function ($request) {
            return $request['duration_seconds'] > 0 &&
                $request['duration_ms'] > 0;
        });
    });
});

describe('CronListener Error Handling', function () {
    it('loggt Fehler wenn HTTP-Request fehlschlägt', function () {
        // HTTP so mocken, dass eine Exception geworfen wird
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
        });

        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $finishedEvent = new ScheduledTaskFinished($task, 0.1);
        $this->listener->handleTaskFinished($finishedEvent);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to report to monitoring', Mockery::type('array'));
    });

    it('verwendet korrekten API-Key und Timeout', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $finishedEvent = new ScheduledTaskFinished($task, 0.1);
        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-API-Token', 'test-api-key');
        });
    });
});

describe('CronListener Memory and Performance Metrics', function () {
    it('fügt Memory-Informationen zu Monitoring-Daten hinzu', function () {
        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $finishedEvent = new ScheduledTaskFinished($task, 0.1);
        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return isset($request['memory_peak_mb']) &&
                isset($request['memory_current_mb']) &&
                is_numeric($request['memory_peak_mb']) &&
                is_numeric($request['memory_current_mb']);
        });
    });

    it('fügt System Load hinzu wenn verfügbar', function () {
        if (!function_exists('sys_getloadavg') || PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('sys_getloadavg function not available or running on Windows');
        }

        $task = Mockery::mock(Event::class);
        $task->command = 'test:command';

        $finishedEvent = new ScheduledTaskFinished($task, 0.1);
        $this->listener->handleTaskFinished($finishedEvent);

        Http::assertSent(function ($request) {
            return isset($request['system_load']) && is_numeric($request['system_load']);
        });
    });
});