<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zentrale Log-URL
    |--------------------------------------------------------------------------
    |
    | URL des zentralen Laravel-Systems, in dem die Logs gesammelt werden
    |
    */
    'central_log_url' => env('CRON_MONITOR_CENTRAL_LOG_URL', 'http://central-log-server.example.com/api/cron-logs'),

    /*
    |--------------------------------------------------------------------------
    | API-Schlüssel für die zentrale Logging-Instanz
    |--------------------------------------------------------------------------
    |
    | API-Schlüssel für die Authentifizierung beim zentralen Log-Server
    |
    */
    'api_key' => env('CRON_MONITOR_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Wartezeit vor Fehleralarm
    |--------------------------------------------------------------------------
    |
    | Zeit in Minuten, nach der ein Cron-Job als fehlgeschlagen gilt,
    | wenn er nicht ausgeführt wurde
    |
    */
    'failure_threshold' => env('CRON_MONITOR_FAILURE_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Zu überwachende Cron-Jobs
    |--------------------------------------------------------------------------
    |
    | Liste der zu überwachenden Cron-Jobs mit erwarteter Ausführungszeit
    |
    */
    'monitored_jobs' => [
        // 'job-name' => [
        //     'schedule' => '* * * * *', // Cron-Format
        //     'description' => 'Beschreibung des Jobs',
        //     'max_runtime' => 60, // Maximale Laufzeit in Sekunden
        // ],
    ],

    'alerts' => [
        'slack_webhook' => env('CRON_MONITOR_SLACK_WEBHOOK', ''),
    ]
];
