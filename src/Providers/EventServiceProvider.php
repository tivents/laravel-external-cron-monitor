<?php

namespace Tivents\CronMonitor\Providers;

use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Tivents\CronMonitor\Listeners\CronListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ScheduledTaskStarting::class => [
            CronListener::class,
        ],
        ScheduledTaskFinished::class => [
            CronListener::class,
        ],
        ScheduledTaskFailed::class => [
            CronListener::class,
        ],
    ];
}