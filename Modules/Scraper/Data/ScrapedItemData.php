<?php

declare(strict_types=1);

namespace Modules\Scraper\Data;

use Spatie\LaravelData\Data;

/**
 * Data class representing a scraped item.
 *
 * @package Modules\Scraper\Data
 */
class ScrapedItemData extends Data
{
    /**
     * @param string $id
     * @param string $url
     * @param string $title
     * @param string|null $price
     * @param string|null $image
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $title,
        public ?string $price,
        public ?string $image,
    ) {}

    /**
     * Get the cleaned price as a float, removing any non-numeric characters.
     *
     * @return float|null The cleaned price or null if price is not set.
     */
    public function getCleanPrice(): ?float
    {
        if (!$this->price) {
            return null;
        }

        return (float) preg_replace('/[^0-9.]/', '', $this->price);
    }
}
