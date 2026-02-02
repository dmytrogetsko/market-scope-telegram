<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Monitor;

use App\Models\TelegraphChat;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Exception;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Models\Monitor;

/**
 * Action to handle monitor failures.
 *
 * @package Modules\Scraper\Actions\Monitor
 */
class HandleMonitorFailureAction
{
    use AsAction;

    private const MAX_FAILURES = 10;

    /**
     * Handle a monitor failure by incrementing the failure count and disabling the monitor if necessary.
     *
     * @param Monitor $monitor The monitor that encountered a failure.
     * @param Exception $e The exception that was thrown during the monitoring process.
     *
     * @return void
     */
    public function handle(Monitor $monitor, Exception $e): void
    {
        $newCount = $monitor->failures_count + 1;
        $monitor->update(['failures_count' => $newCount]);

        Log::warning("[Monitor Failure] Count: {$newCount} | ID: {$monitor->id} | Error: " . $e->getMessage());

        if ($newCount >= self::MAX_FAILURES) {
            $this->disableMonitor($monitor);
        }
    }

    private function disableMonitor(Monitor $monitor): void
    {
        Log::error("[Monitor Disabled] Too many failures. ID: {$monitor->id}");

        $monitor->update(['is_active' => false]);

        // Send notification
        $chat = TelegraphChat::where('user_id', $monitor->user_id)->first();
        if (!$chat) return;

        $message = <<<HTML
⚠️ <b>Моніторинг зупинено!</b>

Ми не змогли отримати дані за посиланням <b>10 разів поспіль</b>.
Можливо, оголошення видалено, категорія змінилась або посилання недійсне.

🔗 <a href="{$monitor->url}">{$monitor->name}</a>

Будь ласка, перевірте посилання і створіть новий монітор, якщо це актуально.
HTML;

        $keyboard = Keyboard::make()->buttons([
            Button::make('🗑 Видалити цей монітор')->action('delete')->param('id', $monitor->id),
        ]);

        $chat->html($message)->keyboard($keyboard)->send();
    }
}
