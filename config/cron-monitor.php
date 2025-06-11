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
    'central_log_url' => env('CRON_MONITOR_CENTRAL_LOG_URL', 'https://monitoring.example.com/api/'),

    /*
    |--------------------------------------------------------------------------
    | API-Schlüssel für die zentrale Logging-Instanz
    |--------------------------------------------------------------------------
    |
    | API-Schlüssel für die Authentifizierung beim zentralen Log-Server
    |
    */
    'api_key' => env('CRON_MONITOR_API_KEY', 'integration-test-key'),

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

    /*
    |--------------------------------------------------------------------------
    | Logging-Einstellungen
    |--------------------------------------------------------------------------
    |
    | Konfiguration für das lokale Logging der Cron-Aktivitäten
    |
    */
    'logging' => [
        'enabled' => env('CRON_MONITOR_LOGGING_ENABLED', false),
        'log_level' => env('CRON_MONITOR_LOG_LEVEL', 'info'), // info, debug, error
        'include_data' => env('CRON_MONITOR_LOG_INCLUDE_DATA', false), // Detaillierte Daten einschließen
    ],

    'alerts' => [
        'slack_webhook' => env('CRON_MONITOR_SLACK_WEBHOOK', ''),
    ]
];