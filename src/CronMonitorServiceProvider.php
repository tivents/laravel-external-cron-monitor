<?php

namespace Tivents\CronMonitor;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\ServiceProvider;
use Tivents\CronMonitor\Console\Commands\CheckCronStatus;
use Tivents\CronMonitor\Listeners\CronListener;
use Tivents\CronMonitor\Providers\EventServiceProvider;
use Tivents\CronMonitor\Services\CronMonitorService;

class CronMonitorServiceProvider extends ServiceProvider
{

    public function boot()
    {
        // Konfigurationsdatei veröffentlichen
        $this->publishes([
            __DIR__ . '/../config/cron-monitor.php' => config_path('cron-monitor.php'),
        ], 'cron-monitor-config');

        // Befehle registrieren
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckCronStatus::class,
            ]);
        }
    }

    public function register()
    {
        // Konfiguration mergen
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cron-monitor.php', 'cron-monitor'
        );

        $this->app->register(EventServiceProvider::class);

        // Service als Singleton registrieren
        $this->app->singleton('cron-monitor', function ($app) {
            return new CronMonitorService();
        });

        // CronListener als Singleton registrieren
        $this->app->singleton(CronListener::class, function ($app) {
            return new CronListener();
        });

    }

}