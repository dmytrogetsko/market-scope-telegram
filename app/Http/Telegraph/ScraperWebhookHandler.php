<?php

declare(strict_types=1);

namespace App\Http\Telegraph;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use Modules\Scraper\Actions\Monitor\CreateMonitorAction;
use Modules\Scraper\Actions\Scrape\ScrapeUrlAction;
use Modules\Scraper\Actions\User\RegisterUserAction;
use Throwable;

class ScraperWebhookHandler extends WebhookHandler
{
    /**
     * Triggered by the /start command.
     */
    public function start(): void
    {
        Log::info('[Webhook] /start command received from chat ID: ' . $this->chat->id);
        // We register the user immediately on start to link the chat
        $this->registerUser();

        $this->chat->html("👋 <b>Вітаю!</b>\n\nЯ допоможу тобі моніторити нові оголошення на OLX.\n\nПросто надішли мені посилання на категорію або пошук OLX, і я буду надсилати тобі нові товари.")
            ->send();
    }

    /**
     * Triggered by the /help command.
     */
    public function help(): void
    {
        $this->chat->html("<b>Як користуватися:</b>\n\n1. Зайди на OLX.ua\n2. Обери фільтри (ціна, місто, категорія)\n3. Скопіюй посилання з браузера\n4. Надішли його мені сюди.\n\nКоманда /stats покаже твої активні монітори.")
            ->send();
    }

    /**
     * Handle incoming text messages.
     */
    protected function handleChatMessage(Stringable $text): void
    {
        $url = trim($text->toString());

        // 1. Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->chat->html("⚠️ Це не схоже на посилання. Будь ласка, надішли коректний URL з OLX.ua")->send();
            return;
        }

        try {
            $user = $this->registerUser();

            $monitor = CreateMonitorAction::run($user, $url);

            $this->chat->html(sprintf(
                "✅ <b>Успішно додано!</b>\n\nЯ почав перевірку.\n📊 Ліміт: <b>%d / %d</b>",
                $user->monitors()->count(),
                $user->monitor_limit
            ))->send();

            // Setup background scraping job
            ScrapeUrlAction::dispatch($monitor);

        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Helper to call the Registration Action using current context.
     */
    protected function registerUser(): \App\Models\User
    {
        // Assuming 'first_name' exists, if not, fallback to username or default
        $name = $this->message?->from()?->firstName()
            ?? $this->message?->from()?->username();

        return RegisterUserAction::run($this->chat, $name);
    }

    /**
     * Centralized exception handling for user feedback.
     */
    protected function handleException(Throwable $e): void
    {
        // Custom message for Limit Reached
        if (str_contains($e->getMessage(), 'limit')) {
            $this->chat->html("🛑 <b>Ліміт вичерпано!</b>\n\nТи досяг максимальної кількості моніторів ({$e->getMessage()}).\n\nВидали старі монітори або очікуй запуску Pro версії.")
                ->keyboard(Keyboard::make()->buttons([
                    Button::make('Мої монітори')->action('list_monitors') // We will implement this later
                ]))
                ->send();
            return;
        }

        // Custom message for Invalid Domain
        if (str_contains($e->getMessage(), 'olx.ua')) {
            $this->chat->html("❌ <b>Невірне посилання</b>\n\nЯ працюю тільки з сайтом <code>olx.ua</code>.")->send();
            return;
        }

        // Generic error (Log it for developer)
        // \Log::error($e);
        $this->chat->html("⚠️ Сталася помилка: " . $e->getMessage())->send();
    }
}
