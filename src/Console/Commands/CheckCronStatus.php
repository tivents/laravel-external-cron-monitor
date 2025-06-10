<?php

namespace Tivents\CronMonitor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckCronStatus extends Command
{
    protected $signature = 'cron:check-status';
    protected $description = 'Überprüft den Status aller überwachten Cron-Jobs';

    public function handle()
    {
        $this->info('Überprüfe Status der überwachten Cron-Jobs...');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('cron-monitor.api_key'),
            ])->get(config('cron-monitor.central_log_url') . '/status');

            $jobs = $response->json('jobs', []);

            $this->table(
                ['Job-Name', 'Status', 'Letzte Ausführung', 'Nächste Ausführung', 'Laufzeit'],
                $this->formatJobsForTable($jobs)
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Fehler bei der Überprüfung der Cron-Jobs: ' . $e->getMessage());
            return 1;
        }
    }

    private function formatJobsForTable(array $jobs): array
    {
        $formatted = [];

        foreach ($jobs as $job) {
            $formatted[] = [
                $job['name'] ?? 'Unbekannt',
                $job['status'] ?? 'Unbekannt',
                $job['last_run'] ?? 'Nie',
                $job['next_run'] ?? 'Unbekannt',
                isset($job['runtime']) ? $job['runtime'] . 's' : 'N/A',
            ];
        }

        return $formatted;
    }
}
