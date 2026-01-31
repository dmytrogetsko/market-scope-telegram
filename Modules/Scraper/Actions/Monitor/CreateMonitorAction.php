<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use App\Models\User;
use Modules\Scraper\Models\Monitor;
use Lorisleiva\Actions\Concerns\AsAction;
use Exception;

/**
 * Action to create a new Monitor for a user.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class CreateMonitorAction
{
    use AsAction;

    /**
     * Create a new Monitor for the given user and URL.
     *
     * @param User $user The user creating the monitor.
     * @param string $url The URL to monitor.
     *
     * @return Monitor
     *
     * @throws Exception
     */
    public function handle(User $user, string $url): Monitor
    {
        // Check Limits based on dynamic user settings
        if ($user->monitors()->count() >= $user->monitor_limit) {
            throw new Exception(
                "You have reached your limit of {$user->monitor_limit} monitors. Upgrade to *PRO* for more."
            );
        }

        if (!str_contains($url, 'olx.ua')) {
            throw new Exception("Only *olx.ua* links are supported.");
        }

        return Monitor::create([
            'user_id' => $user->id,
            'url' => $url,
            'is_active' => true,
            'name' => 'Search ' . now()->format('d.m'),
        ]);
    }
}
