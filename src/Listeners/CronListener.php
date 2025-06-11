<?php

namespace Tivents\CronMonitor\Listeners;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CronListener
{
    public function __construct() {}

    public function handle($event)
    {
        if ($event instanceof ScheduledTaskStarting) {
            $this->handleTaskStarting($event);
        } elseif ($event instanceof ScheduledTaskFinished) {
            $this->handleTaskFinished($event);
        } elseif ($event instanceof ScheduledTaskFailed) {
            $this->handleTaskFailed($event);
        }
    }

    public function handleTaskStarting(ScheduledTaskStarting $event)
    {
        $taskId = $this->generateTaskId($event->task);
        $startTime = microtime(true);

        // Startzeit im Cache speichern für spätere Duration-Berechnung
        Cache::put("cron_start_{$taskId}", $startTime, 3600);
    }

    public function handleTaskFinished(ScheduledTaskFinished $event)
    {
        $taskId = $this->generateTaskId($event->task);
        $endTime = microtime(true);
        $startTime = Cache::get("cron_start_{$taskId}");
        $duration = $startTime ? ($endTime - $startTime) : ($event->runtime ?? 0);

        // Cache aufräumen
        Cache::forget("cron_start_{$taskId}");

        $this->reportToMonitoring([
            'status' => 'finished',
            'command' => $event->task->command ?? $event->task->description,
            'application' => config('app.name'),
            'timestamp' => now(),
            'task_id' => $taskId,
            'duration_seconds' => round($duration, 3),
            'duration_ms' => round($duration * 1000, 2),
            'runtime' => $event->runtime ?? 0, // Laravel's eingebaute Runtime
            'end_time' => $endTime,
        ]);
    }

    public function handleTaskFailed(ScheduledTaskFailed $event)
    {
        $taskId = $this->generateTaskId($event->task);
        $endTime = microtime(true);
        $startTime = Cache::get("cron_start_{$taskId}");
        $duration = $startTime ? ($endTime - $startTime) : 0;

        Cache::forget("cron_start_{$taskId}");

        $this->reportToMonitoring([
            'status' => 'failed',
            'command' => $event->task->command ?? $event->task->description,
            'application' => config('app.name'),
            'timestamp' => now(),
            'task_id' => $taskId,
            'duration_seconds' => round($duration, 3),
            'duration_ms' => round($duration * 1000, 2),
            'exception' => $event->exception->getMessage(),
            'end_time' => $endTime,
        ]);
    }

    private function sendPerformanceAlert(array $data)
    {
        $minutes = round($data['duration_seconds'] / 60, 1);
        Http::post(config('cron-monitor.alerts.slack_webhook'), [
            'text' => "⚠️ Long Running Cron Job: {$data['command']} took {$minutes} minutes in {$data['application']}",
        ]);
    }

    private function generateTaskId($task): string
    {
        return md5(($task->command ?? $task->description).config('app.name'));
    }

    private function reportToMonitoring(array $data)
    {
        // Memory Usage hinzufügen
        $data['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $data['memory_current_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);

        // System Load (falls verfügbar - nur auf Unix-Systemen)
        if (function_exists('sys_getloadavg') && PHP_OS_FAMILY !== 'Windows') {
            $load = sys_getloadavg();
            $data['system_load'] = $load[0]; // 1-Minute Load Average
        }

        // CPU-Nutzung ermitteln (betriebssystemspezifisch)
        $cpuUsage = $this->getCpuUsage();
        if ($cpuUsage !== null) {
            $data['cpu_usage'] = $cpuUsage;
        }

        try {
            Http::timeout(5)->withHeaders(['X-API-Token' => config('cron-monitor.api_key')])
                ->post(config('cron-monitor.central_log_url'), $data)->json();
        } catch (\Exception $e) {
            Log::error('Failed to report to monitoring', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Ermittelt die aktuelle CPU-Nutzung des Prozesses
     *
     * @return float|null CPU-Nutzung in Prozent oder null, wenn nicht ermittelbar
     */
    private function getCpuUsage(): ?float
    {
        // Frühe Rückgabe für Windows-Systeme
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->getWindowsCpuUsage();
        }

        // Linux/Unix-Systeme
        return $this->getUnixCpuUsage();
    }

    /**
     * CPU-Nutzung für Windows-Systeme ermitteln
     *
     * @return float|null
     */
    private function getWindowsCpuUsage(): ?float
    {
        if (!function_exists('exec')) {
            return null;
        }

        try {
            // Windows: Verwende wmic für CPU-Nutzung
            exec('wmic cpu get loadpercentage /value 2>nul', $output, $returnCode);

            if ($returnCode === 0) {
                foreach ($output as $line) {
                    if (strpos($line, 'LoadPercentage=') === 0) {
                        $value = trim(str_replace('LoadPercentage=', '', $line));
                        if (is_numeric($value)) {
                            return (float) $value;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Windows CPU-Nutzung konnte nicht ermittelt werden: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * CPU-Nutzung für Unix/Linux-Systeme ermitteln
     *
     * @return float|null
     */
    private function getUnixCpuUsage(): ?float
    {
        if (!function_exists('exec')) {
            return null;
        }

        try {
            $pid = getmypid();
            // CPU-Nutzung des aktuellen Prozesses über 'ps' abfragen
            exec("ps -p $pid -o %cpu 2>/dev/null | tail -n 1", $output, $returnCode);

            if ($returnCode === 0 && isset($output[0])) {
                $cpuValue = trim($output[0]);
                if (is_numeric($cpuValue)) {
                    return (float) $cpuValue;
                }
            }
        } catch (\Exception $e) {
            Log::debug('Unix CPU-Nutzung über ps konnte nicht ermittelt werden: ' . $e->getMessage());
        }

        // Fallback: Systemweite CPU-Nutzung über /proc/stat
        return $this->getLinuxSystemCpuUsage();
    }

    /**
     * Systemweite CPU-Nutzung über /proc/stat ermitteln (Linux)
     *
     * @return float|null
     */
    private function getLinuxSystemCpuUsage(): ?float
    {
        if (!file_exists('/proc/stat')) {
            return null;
        }

        try {
            // Systemweite CPU-Nutzung über /proc/stat ermitteln
            $stat1 = file('/proc/stat');
            if (!$stat1) return null;

            $cpuData1 = explode(' ', $stat1[0]);
            usleep(100000); // 100ms warten

            $stat2 = file('/proc/stat');
            if (!$stat2) return null;

            $cpuData2 = explode(' ', $stat2[0]);

            // CPU-Werte extrahieren (user, nice, system, idle)
            $cpu1 = array_slice($cpuData1, 2, 4);
            $cpu2 = array_slice($cpuData2, 2, 4);

            $total1 = array_sum($cpu1);
            $total2 = array_sum($cpu2);

            $idle1 = $cpu1[3];
            $idle2 = $cpu2[3];

            $deltaTotal = $total2 - $total1;
            $deltaIdle = $idle2 - $idle1;

            if ($deltaTotal > 0) {
                $cpuUsage = 100 * (1 - $deltaIdle / $deltaTotal);
                return round($cpuUsage, 2);
            }
        } catch (\Exception $e) {
            Log::debug('Linux CPU-Nutzung über /proc/stat konnte nicht ermittelt werden: ' . $e->getMessage());
        }

        return null;
    }
}