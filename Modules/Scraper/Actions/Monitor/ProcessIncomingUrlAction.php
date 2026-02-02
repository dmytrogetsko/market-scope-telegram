<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Actions\Scrape\ScrapeUrlAction;
use Modules\Scraper\Models\Monitor;
use InvalidArgumentException;

/**
 * Action to process an incoming URL, create a monitor, and dispatch a scrape job.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class ProcessIncomingUrlAction
{
    use AsAction;

    /**
     * Process an incoming URL to create a monitor and dispatch a scrape job.
     *
     * @param User $user The user requesting the monitor creation.
     * @param string $url The URL to be monitored.
     *
     * @return Monitor
     *
     * @throws InvalidArgumentException
     */
    public function handle(User $user, string $url): Monitor
    {
        // 1. Validation logic
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL format.");
        }

        if (!str_contains($url, 'olx.ua')) {
            throw new InvalidArgumentException("Only olx.ua domain is supported.");
        }

        // 2. Creation logic (delegates to CreateMonitorAction)
        // Ensure CreateMonitorAction checks the user limits internally
        $monitor = CreateMonitorAction::run($user, $url);

        // 3. Dispatch Scraper
        ScrapeUrlAction::dispatch($monitor);

        Log::info("[Monitor Created] User ID: {$user->id} | URL: {$url}");

        return $monitor;
    }
}
