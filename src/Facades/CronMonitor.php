<?php

namespace Tivents\CronMonitor\Facades;

use Illuminate\Support\Facades\Facade;

class CronMonitor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cron-monitor';
    }
}
