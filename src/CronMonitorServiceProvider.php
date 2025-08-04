<?php

namespace Tivents\CronMonitor;

use Illuminate\Support\ServiceProvider;
use Tivents\CronMonitor\Console\Commands\CheckCronStatus;
use Tivents\CronMonitor\Listeners\CronListener;
use Tivents\CronMonitor\Providers\EventServiceProvider;
use Tivents\CronMonitor\Services\CronMonitorService;

class CronMonitorServiceProvider extends ServiceProvider
{

    public function boot()
    {
        // Befehle registrieren
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/cron-monitor.php' => config_path('cron-monitor.php'),
            ], 'cron-monitor-config');

            $this->commands([
                CheckCronStatus::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cron-monitor.php', 'cron-monitor'
        );

        $this->app->register(EventServiceProvider::class);
    }
}