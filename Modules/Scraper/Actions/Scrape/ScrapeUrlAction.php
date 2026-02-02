<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Scrape;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Actions\Monitor\HandleMonitorFailureAction;
use Modules\Scraper\Data\ScraperResponseData;
use Modules\Scraper\Models\Monitor;

/**
 * Action to scrape a URL using an external Python scraper service.
 * Refactored to act as an Orchestrator.
 *
 * @package Modules\Scraper\Actions\Scrape
 */
class ScrapeUrlAction
{
    use AsAction;

    // Horizon Job Settings
    public int $jobTries = 3;
    public int $jobBackoff = 60;
    public int $jobTimeout = 120;

    /**
     * Main handler to perform the scraping of a monitor's URL.
     *
     * @param Monitor $monitor
     * @return void
     *
     * @throws ConnectionException
     */
    public function handle(Monitor $monitor): void
    {
        if (!$monitor->is_active) {
            return;
        }

        Log::info("[ProcessMonitor] Start check for Monitor ID: {$monitor->id}");

        try {
            // 1. Prepare Request Data
            $existingIds = $monitor->listings()
                ->orderBy('posted_at', 'desc')
                ->limit(80)
                ->pluck('external_id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $isFirstRun = empty($existingIds);

            // 2. Execute Request
            $host = (string) config('app.internal.scraper_host', 'http://python-scraper:8000');

            $response = Http::timeout(60)->post("{$host}/scrape", [
                'url'          => $monitor->url,
                'existing_ids' => $existingIds,
            ]);

            if ($response->failed()) {
                throw new Exception("Scraper API failed: " . $response->body());
            }

            // 3. Handle Success
            // Reset failures if we succeeded
            if ($monitor->failures_count > 0) {
                $monitor->update(['failures_count' => 0]);
            }

            $data = ScraperResponseData::from($response->json());

            // 4. Delegate Processing (DB updates & Notifications)
            $newCount = ProcessScrapedResponseAction::run($monitor, $data, $isFirstRun);

            Log::info("[ProcessMonitor] Success. Found {$newCount} new items.", ['monitor_id' => $monitor->id]);
        } catch (Exception $e) {
            // 5. Handle Failure (Counter++ & Disable if needed)
            HandleMonitorFailureAction::run($monitor, $e);

            // Rethrow for Horizon retries (only if not disabled)
            if ($monitor->refresh()->is_active) {
                throw $e;
            }
        }
    }
}
