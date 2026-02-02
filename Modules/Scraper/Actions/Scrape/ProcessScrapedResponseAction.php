<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Scrape;

use App\Models\TelegraphChat;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Actions\Notification\SendNewListingNotificationAction;
use Modules\Scraper\Actions\Notification\SendPriceChangeNotificationAction;
use Modules\Scraper\Data\ScrapedItemData;
use Modules\Scraper\Data\ScraperResponseData;
use Modules\Scraper\Models\Monitor;

/**
 * Action to process the scraped response data for a monitor.
 *
 * @package Modules\Scraper\Actions\Scrape
 */
class ProcessScrapedResponseAction
{
    use AsAction;

    /**
     * Process the scraped data for a given monitor.
     *
     * @param Monitor $monitor
     * @param ScraperResponseData $data
     * @param bool $isFirstRun
     *
     * @return int
     */
    public function handle(Monitor $monitor, ScraperResponseData $data, bool $isFirstRun): int
    {
        // Update Monitor Metadata
        if (($data->page_title ?? null) && $monitor->name !== $data->page_title) {
            $monitor->update(['name' => $data->page_title]);
        }
        $monitor->touch('last_checked_at');

        // Fetch Chat Context
        $telegramChat = TelegraphChat::where('user_id', $monitor->user_id)->first();
        $newItemsCount = 0;

        /** @var ScrapedItemData $item */
        foreach ($data->listings as $item) {
            $listing = $monitor->listings()->firstOrNew(['external_id' => $item->id]);

            $oldPrice = (float) $listing->price;
            $newPrice = (float) $item->getCleanPrice();
            $wasExisting = $listing->exists;

            $listing->fill([
                'url'       => $item->url,
                'title'     => $item->title,
                'price'     => $newPrice,
                'image_url' => $item->image,
                'posted_at' => now(),
                'raw_data'  => $item->toArray(),
            ]);

            // 1. Check Price Change
            if ($wasExisting && !$isFirstRun && $telegramChat) {
                if (abs($oldPrice - $newPrice) > 1) {
                    SendPriceChangeNotificationAction::run($telegramChat, $listing, $oldPrice, $newPrice);
                }
            }

            $listing->save();

            // 2. Check New Listing
            if ($listing->wasRecentlyCreated) {
                $newItemsCount++;
                if (!$isFirstRun && $telegramChat) {
                    SendNewListingNotificationAction::run($telegramChat, $listing);
                }
            }
        }

        return $newItemsCount;
    }
}
