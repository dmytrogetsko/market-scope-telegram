<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Action to delete a monitor for a user.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class DeleteMonitorAction
{
    use AsAction;

    /**
     * Deletes a monitor belonging to the specified user.
     *
     * @param User $user The user who owns the monitor.
     * @param string|int $monitorId The ID of the monitor to delete.
     *
     * @return void
     */
    public function handle(User $user, string|int $monitorId): void
    {
        $monitor = $user->monitors()->findOrFail($monitorId);
        $monitor->delete();
    }
}
