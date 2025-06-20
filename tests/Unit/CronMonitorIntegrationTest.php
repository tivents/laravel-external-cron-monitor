<?php

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Http;
use Tivents\CronMonitor\Listeners\CronListener;

beforeEach(function () {
    Http::fake();
    setupMonitoringConfig();
});

describe('Cron Monitor Integration', function () {
    it('überwacht kompletten Task-Lebenszyklus', function () {
        // Event Listener registrieren
        EventFacade::listen(ScheduledTaskStarting::class, [CronListener::class, 'handleTaskStarting']);
        EventFacade::listen(ScheduledTaskFinished::class, [CronListener::class, 'handleTaskFinished']);
        EventFacade::listen(ScheduledTaskFailed::class, [CronListener::class, 'handleTaskFailed']);

        $task = Mockery::mock(Event::class);
        $task->command = 'integration:test';

        // Simuliere kompletten Task-Durchlauf
        EventFacade::dispatch(new ScheduledTaskStarting($task));

        usleep(10000); // 10ms Ausführungszeit simulieren

        EventFacade::dispatch(new ScheduledTaskFinished($task, 0.01));

        // Prüfe, dass Monitoring-Call gemacht wurde
        Http::assertSent(function ($request) {
            return $request->url() === MONITOR_TEST_URL &&
                $request['status'] === 'finished' &&
                $request['command'] === 'integration:test' &&
                $request['application'] === TEST_APP_NAME &&
                $request->hasHeader('X-API-Token', MONITOR_TEST_API_KEY);
        });
    });

    it('behandelt fehlgeschlagene Tasks korrekt in Integration', function () {
        EventFacade::listen(ScheduledTaskStarting::class, [CronListener::class, 'handleTaskStarting']);
        EventFacade::listen(ScheduledTaskFailed::class, [CronListener::class, 'handleTaskFailed']);

        $task = Mockery::mock(Event::class);
        $task->command = 'integration:failing-test';

        EventFacade::dispatch(new ScheduledTaskStarting($task));

        $exception = new Exception('Integration test failure');
        EventFacade::dispatch(new ScheduledTaskFailed($task, $exception));

        Http::assertSent(function ($request) {
            return $request['status'] === 'failed' &&
                $request['command'] === 'integration:failing-test' &&
                $request['exception'] === 'Integration test failure';
        });
    });

    it('verarbeitet mehrere parallele Tasks korrekt', function () {
        EventFacade::listen(ScheduledTaskStarting::class, [CronListener::class, 'handleTaskStarting']);
        EventFacade::listen(ScheduledTaskFinished::class, [CronListener::class, 'handleTaskFinished']);

        $task1 = Mockery::mock(Event::class);
        $task1->command = 'task:one';

        $task2 = Mockery::mock(Event::class);
        $task2->command = 'task:two';

        // Starte beide Tasks
        EventFacade::dispatch(new ScheduledTaskStarting($task1));
        EventFacade::dispatch(new ScheduledTaskStarting($task2));

        // Beende beide Tasks
        EventFacade::dispatch(new ScheduledTaskFinished($task1, 0.1));
        EventFacade::dispatch(new ScheduledTaskFinished($task2, 0.2));

        // Erwarte zwei separate Monitoring-Calls
        Http::assertSentCount(4);

        Http::assertSent(function ($request) {
            return $request['command'] === 'task:one';
        });

        Http::assertSent(function ($request) {
            return $request['command'] === 'task:two';
        });
    });
});
