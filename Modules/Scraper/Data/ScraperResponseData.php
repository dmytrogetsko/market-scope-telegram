<?php

declare(strict_types=1);

namespace Modules\Scraper\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;

/**
 * Data class representing the response from a scraper.
 *
 * @package Modules\Scraper\Data
 */
class ScraperResponseData extends Data
{
    /**
     * @param string|null $page_title The title of the scraped page.
     * @param array<int, ScrapedItemData> $listings An array of scraped item data.
     */
    public function __construct(
        public ?string $page_title,

        #[DataCollectionOf(ScrapedItemData::class)]
        public array $listings,
    ) {}
}
