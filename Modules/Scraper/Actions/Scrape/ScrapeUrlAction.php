<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Scrape;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Data\ScraperResponseData;
use Modules\Scraper\Models\Monitor;

/**
 * Action to scrape a URL using an external Python scraper service.
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
     * Handle the scraping of the given monitor's URL.
     *
     * @param Monitor $monitor The monitor instance containing the URL to scrape.
     *
     * @return void
     *
     * @throws \Exception If the scraping fails or the response is invalid.
     */
    public function handle(Monitor $monitor): void
    {
        Log::info("[ProcessMonitor] Start check for Monitor ID: {$monitor->id}");

        $host = (string) config('app.internal.scraper_host', 'http://python-scraper:8000');

        try {
            $response = Http::timeout(45)->post("{$host}/scrape", [
                'url' => $monitor->url,
            ]);

            if ($response->failed()) {
                throw new \Exception("Scraper API failed: " . $response->body());
            }

            $data = ScraperResponseData::from($response->json());

            $monitor->update([
                'last_checked_at' => now(),
                'name' => $data->page_title ?? $monitor->name,
            ]);

            $newItemsCount = 0;

            foreach ($data->listings as $item) {
                $listing = $monitor->listings()->updateOrCreate(
                    ['external_id' => $item->id],
                    [
                        'url'       => $item->url,
                        'title'     => $item->title,
                        'price'     => $item->getCleanPrice(),
                        'image_url' => $item->image,
                        'posted_at' => now(),
                        'raw_data'  => $item->toArray(),
                    ]
                );

                if ($listing->wasRecentlyCreated) {
                    $newItemsCount++;
                    // $this->notifyUser($monitor->user->chat_id, $listing);
                }
            }

            Log::info("[ProcessMonitor] Success. Found {$newItemsCount} new items.");

        } catch (\Exception $e) {
            Log::error("[ProcessMonitor] Failed: " . $e->getMessage());

            // Rethrow to let Horizon handle retries
            throw $e;
        }
    }
}
