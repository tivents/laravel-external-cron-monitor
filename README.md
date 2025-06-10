# Laravel Cron Monitor

[![Tests](https://github.com/tivents/laravel-cron-monitor/workflows/tests/badge.svg)](https://github.com/tivents/laravel-cron-monitor/actions)
[![Latest Stable Version](https://poser.pugx.org/tivents/laravel-cron-monitor/v/stable)](https://packagist.org/packages/tivents/laravel-cron-monitor)
[![License](https://poser.pugx.org/tivents/laravel-cron-monitor/license)](https://packagist.org/packages/tivents/laravel-cron-monitor)

A Laravel package for external monitoring of scheduled tasks (cron jobs). This package automatically tracks the execution of your Laravel scheduled tasks and reports metrics to an external monitoring service.

## Features

- **Automatic Task Monitoring**: Automatically monitors all Laravel scheduled tasks
- **Performance Metrics**: Tracks execution time, memory usage, and system performance
- **Error Reporting**: Captures and reports failed tasks with exception details
- **External Reporting**: Sends monitoring data to external services via HTTP API
- **System Metrics**: Includes CPU usage, memory consumption, and system load
- **Configurable**: Easy configuration for different environments
- **Singleton Pattern**: Efficient resource usage with singleton design
- **Cross-Platform**: Works on Windows, Linux, and macOS

## Installation

You can install the package via Composer:#

```bash 
composer require tivents/laravel-cron-monitor
```

### Laravel Auto-Discovery

The package will automatically register itself via Laravel's package auto-discovery feature.

For Laravel versions < 5.5, you need to manually register the service provider in `config/app.php`:

```php 
'providers' => [ // Other Service Providers Tivents\CronMonitor\CronMonitorServiceProvider::class, ];
```

## Configuration

Publish the configuration file:
```bash
php artisan vendor:publish --tag=cron-monitor-config
```

This will create a `config/cron-monitor.php` file with the following structure:
```php
env('CRON_MONITOR_API_KEY'), 
'endpoint' => env('CRON_MONITOR_ENDPOINT'), 
/* |-------------------------------------------------------------------------- | Performance Monitoring |-------------------------------------------------------------------------- */ 
'track_performance' => env('CRON_MONITOR_TRACK_PERFORMANCE', true), 
'track_memory' => env('CRON_MONITOR_TRACK_MEMORY', true), 
'track_cpu' => env('CRON_MONITOR_TRACK_CPU', true), 
/* |-------------------------------------------------------------------------- | Alert Configuration |-------------------------------------------------------------------------- */ 
'alerts' => [ 
    'slack_webhook' => env('CRON_MONITOR_SLACK_WEBHOOK'), 
    ], 
];
```

### Environment Variables

Add the following variables to your `.env` file:

```dotenv
CRON_MONITOR_API_KEY=your-api-key-here 
CRON_MONITOR_ENDPOINT=[https://your-monitoring-service.com/api/](https://your-monitoring-service.com/api/)
CRON_MONITOR_TRACK_PERFORMANCE=true
CRON_MONITOR_TRACK_MEMORY=true
CRON_MONITOR_TRACK_CPU=true
CRON_MONITOR_SLACK_WEBHOOK=[https://hooks.slack.com/services/your/slack/webhook](https://hooks.slack.com/services/your/slack/webhook)
```

## Usage

Once installed and configured, the package will automatically monitor all your Laravel scheduled tasks. No additional code changes are required.

### Scheduled Tasks

The package monitors these Laravel console events:
- `ScheduledTaskStarting` - When a task begins execution
- `ScheduledTaskFinished` - When a task completes successfully
- `ScheduledTaskFailed` - When a task fails with an exception

### Monitored Data

For each task execution, the following data is collected and sent to your monitoring service:

- **Basic Information**:
    - Task command/description
    - Application name
    - Execution timestamp
    - Task ID (unique identifier)
    - Status (finished/failed)

- **Performance Metrics**:
    - Duration in seconds and milliseconds
    - Memory usage (peak and current)
    - CPU usage (when available)
    - System load (Unix/Linux systems)

- **Error Details** (for failed tasks):
    - Exception message
    - Full error context

### Example Monitored Task
```php
// In your app/Console/Kernel.php
protected function schedule(Schedule schedule) {
$schedule->command('emails:send') ->daily() ->at('08:00');
$schedule->command('backup:run')
         ->weekly()
         ->sundays()
         ->at('02:00');
}
```

Both of these tasks will be automatically monitored without any additional configuration.

## API Payload Example

The package sends HTTP POST requests to your configured endpoint with payloads like this:
 ```json
{
  "status": "finished",
  "command": "emails:send",
  "application": "MyApp",
  "timestamp": "2025-06-10T14:30:00+00:00",
  "task_id": "a1b2c3d4e5f6g7h8i9j0",
  "duration_seconds": 2.847,
  "duration_ms": 2847.23,
  "runtime": 2.8,
  "memory_peak_mb": 45.2,
  "memory_current_mb": 38.7,
  "cpu_usage": 15.3,
  "system_load": 0.8,
  "end_time": 1749582600.123
}
```

For failed tasks:
```json
{ 
  "status": "failed", 
  "command": "backup:run", 
  "application": "MyApp", 
  "timestamp": "2025-06-10T02:15:30+00:00", 
  "task_id": "b2c3d4e5f6g7h8i9j0k1", 
  "duration_seconds": 45.2, 
  "duration_ms": 45200.0, 
  "memory_peak_mb": 128.5, 
  "memory_current_mb": 95.3, 
  "exception": "Database connection timeout", 
  "end_time": 1749582930.456
}
```

## Commands

The package includes a command to check the status of your cron monitoring:
```shell
php artisan cron:status
```
This command will verify your configuration and test the connection to your monitoring service.

## Testing

Run the test suite:
```shell
composer test
```
Or run tests with coverage:
```shell
composer test-coverage
```

The package includes comprehensive tests for:
- Service provider registration
- Event listener functionality
- HTTP request handling
- Error scenarios
- Performance metric collection
- Cross-platform compatibility

## Platform Support

### Windows
- CPU usage via `wmic` command
- Memory tracking
- Full task monitoring

### Linux/Unix
- CPU usage via `/proc/stat` and `ps` command
- System load via `sys_getloadavg()`
- Memory tracking
- Full task monitoring

### macOS
- CPU usage via `ps` command
- System load tracking
- Memory tracking
- Full task monitoring

## Error Handling

The package includes robust error handling:

- **HTTP Failures**: If the monitoring service is unavailable, errors are logged locally
- **CPU Detection Failures**: Gracefully handles platforms where CPU usage cannot be determined
- **Memory Failures**: Continues operation even if memory metrics are unavailable
- **Exception Capture**: Failed tasks are still monitored and reported

All errors are logged to your Laravel log files for debugging.

## Security

- API keys are securely handled via environment variables
- HTTP requests include timeout protection (5 seconds default)
- No sensitive data is transmitted unless explicitly configured
- Local fallback logging for monitoring service outages

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for details on what has changed.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- [Willi Helwig](https://github.com/willihelwig)
- [TIVENTS](https://github.com/tivents)
- [All Contributors](../../contributors)

## Support

If you discover any security vulnerabilities, please send an email to [it@tivents.de](mailto:it@tivents.de).

For questions and support, please use the [GitHub issues](https://github.com/tivents/laravel-cron-monitor/issues).