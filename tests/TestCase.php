<?php

namespace Tivents\CronMonitor\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tivents\CronMonitor\CronMonitorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Zusätzliche Setup-Schritte...
    }

    protected function getPackageProviders($app)
    {
        return [
            CronMonitorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Vorhandene Einstellungen
        $app['config']->set('cron-monitor.central_log_url', 'http://test-server.com/api/cron-logs');
        $app['config']->set('cron-monitor.api_key', 'test-api-key');

        // Neue Logging-Einstellungen
        $app['config']->set('cron-monitor.logging.enabled', true);
        $app['config']->set('cron-monitor.logging.log_level', 'debug');
        $app['config']->set('cron-monitor.logging.include_data', true);
    }

}