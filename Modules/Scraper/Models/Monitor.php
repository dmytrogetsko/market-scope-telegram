<?php

declare(strict_types=1);

namespace Modules\Scraper\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $url
 * @property string $name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_checked_at
 * @property array<string, mixed>|null $filters
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\Modules\Scraper\Models\Listing[] $listings
 */
class Monitor extends Model
{
    /**
     * @var list<string> The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'url',
        'name',
        'is_active',
        'last_checked_at',
        'filters',
    ];

    /**
     * @var array<string, string> Specifies how attributes should be cast when accessed.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'filters' => 'array',
    ];

    /**
     * Get the listings associated with the monitor.
     *
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * Get the user that owns the monitor.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
