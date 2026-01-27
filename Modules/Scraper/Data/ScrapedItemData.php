<?php

declare(strict_types=1);

namespace Modules\Scraper\Data;

use Spatie\LaravelData\Data;

class ScrapedItemData extends Data
{
    /**
     * @param string $external_id
     * @param string $title
     * @param float|null $price
     * @param string $url
     * @param string|null $image_url
     */
    public function __construct(
        public string $external_id,
        public string $title,
        public ?float $price,
        public string $url,
        public ?string $image_url = null,
    ) {}
}
