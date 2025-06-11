<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use Tivents\CronMonitor\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);


/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
/*
|--------------------------------------------------------------------------
| Test Constants
|--------------------------------------------------------------------------
|
| Hier definieren wir zentrale Konstanten für unsere Tests, die in allen
| Testdateien verwendet werden können.
|
*/

const MONITOR_TEST_URL = 'https://monitoring.example.com/api/';
const MONITOR_TEST_API_KEY = 'integration-test-key';
const TEST_APP_NAME = 'integration-test-app';

/*
|--------------------------------------------------------------------------
| Test Setup Functions
|--------------------------------------------------------------------------
|
| Zentrale Funktionen für das Setup von Tests
|
*/

function setupMonitoringConfig()
{
    config([
        'app.name' => TEST_APP_NAME,
        'cron-monitor.central_log_url' => MONITOR_TEST_URL,
        'cron-monitor.api_key' => MONITOR_TEST_API_KEY,
        'cron-monitor.alerts.slack_webhook' => MONITOR_TEST_API_KEY,
    ]);
}
