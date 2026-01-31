<?php

declare(strict_types=1);

namespace Modules\Scraper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *  Represents a concrete product scraped from an external source.
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $external_id
 * @property string $url
 * @property string $title
 * @property float|null $price
 * @property string|null $image_url
 * @property array<string, mixed>|null $raw_data
 * @property \Illuminate\Support\Carbon $posted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Monitor $monitor
 */
class Listing extends Model
{
    /**
     * @var list<string> The attributes that are mass assignable.
     */
    protected $fillable = [
        'monitor_id',
        'external_id',
        'url',
        'title',
        'price',
        'image_url',
        'raw_data',
        'posted_at',
    ];

    /**
     * @var array<string, string> Specifies how attributes should be cast when accessed.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'posted_at' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get the monitor that owns the listing.
     *
     * @return BelongsTo<Monitor, $this>
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
