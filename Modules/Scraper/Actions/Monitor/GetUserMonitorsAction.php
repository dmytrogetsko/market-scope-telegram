<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Models\Monitor;

/**
 * Action to retrieve all monitors for a given user.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class GetUserMonitorsAction
{
    use AsAction;

    /**
     * Retrieve all monitors for a given user, ordered by creation date descending.
     *
     * @param User $user The user whose monitors are to be retrieved.
     *
     * @return Collection<int, Monitor>
     *
     * @return Collection<int, Monitor>
     */
    public function handle(User $user): Collection
    {
        return $user->monitors()
            ->orderByDesc('created_at')
            ->get();
    }
}
