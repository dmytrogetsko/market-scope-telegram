<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Models\Monitor;

/**
 * Action to toggle the active status of a monitor.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class ToggleMonitorStatusAction
{
    use AsAction;

    /**
     * Toggle the active status of a monitor for a given user.
     *
     * @param User $user The user who owns the monitor.
     * @param string|int $monitorId The ID of the monitor to toggle.
     * @param bool $status The new status to set (true for active, false for inactive).
     *
     * @return Monitor The updated monitor instance.
     *
     * @throws ModelNotFoundException
     */
    public function handle(User $user, string|int $monitorId, bool $status): Monitor
    {
        /** @var Monitor $monitor */
        $monitor = $user->monitors()->findOrFail($monitorId);

        $monitor->update(['is_active' => $status]);

        return $monitor;
    }
}
