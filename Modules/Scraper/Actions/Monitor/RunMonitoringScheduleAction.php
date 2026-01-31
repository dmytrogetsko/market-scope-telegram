<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Models\Monitor;
use Modules\Scraper\Actions\Scrape\ScrapeUrlAction;
use Illuminate\Support\Facades\Log;

/**
 * Action to run the monitoring schedule, dispatching scrape jobs for active monitors.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class RunMonitoringScheduleAction
{
    use AsAction;

    // Allows to run this action as an Artisan command
    // php artisan scraper:run-schedule
    public string $commandSignature = 'scraper:run-schedule';

    public function handle(): void
    {
        Log::info("[Scheduler] Start checking monitors...");

        Monitor::where('is_active', true)
            ->chunk(100, function ($monitors) {
                foreach ($monitors as $monitor) {
                    // For each monitor, dispatch a job to scrape its URL
                    ScrapeUrlAction::dispatch($monitor);
                }
            });

        Log::info("[Scheduler] All jobs dispatched to Horizon.");
    }
}
