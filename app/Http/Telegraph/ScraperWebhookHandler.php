<?php

declare(strict_types=1);

namespace App\Http\Telegraph;

use App\Models\User;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use Modules\Scraper\Actions\Monitor\DeleteMonitorAction;
use Modules\Scraper\Actions\Monitor\GetUserMonitorsAction;
use Modules\Scraper\Actions\Monitor\ProcessIncomingUrlAction;
use Modules\Scraper\Actions\Monitor\ToggleMonitorStatusAction;
use Modules\Scraper\Actions\User\RegisterUserAction;
use Throwable;

/**
 * Telegram Webhook Handler for the Scraper Bot.
 *
 * Handles commands and messages to manage OLX monitoring.
 *
 * @package App\Http\Telegraph
 */
class ScraperWebhookHandler extends WebhookHandler
{
    /**
     * Triggered by the /start command.
     */
    public function start(): void
    {
        Log::info('[Webhook] /start command received', ['chat_id' => $this->chat->id]);
        $this->registerUser();

        $this->reply(<<<'HERE'
👋 <b>Вітаю у MarketScope!</b>

Я тримаю руку на пульсі всього ринку OLX. Поки ти спиш — я сканую.

<b>Що я вмію:</b>
🚀 <b>Миттєвий пошук:</b> Сповіщаю про нові товари швидше за інших.
📉 <b>Контроль цін:</b> Якщо продавець знизить ціну хоч на гривню — ти дізнаєшся першим.
🗑 <b>Фільтр сміття:</b> Я пам'ятаю історію і не спамлю старими оголошеннями.

<b>Як почати?</b> Просто надішли мені посилання на категорію або пошук з OLX.ua 👇
HERE
        );
    }

    /**
     * Triggered by the /help command.
     */
    public function help(): void
    {
        $this->reply("<b>Як користуватися:</b>\n\n1. Зайди на OLX.ua\n2. Обери фільтри (ціна, місто, категорія)\n3. Скопіюй посилання з браузера\n4. Надішли його мені сюди.\n\nКоманда /list покаже твої активні монітори.");
    }

    /**
     * Handle incoming text messages.
     */
    protected function handleChatMessage(Stringable $text): void
    {
        try {
            $user = $this->registerUser();
            $url = trim($text->toString());

            // Delegate logic to Action
            $monitor = ProcessIncomingUrlAction::run($user, $url);

            $this->reply(sprintf(
                "✅ <b>Успішно додано!</b>\n\nЯ почав перевірку.\n📊 Ліміт: <b>%d / %d</b>",
                $user->monitors()->count(),
                $user->monitor_limit
            ));

        } catch (InvalidArgumentException $e) {
            // Map technical exceptions to user-friendly Ukrainian text
            if (str_contains($e->getMessage(), 'URL')) {
                $this->reply("⚠️ Це не схоже на посилання. Будь ласка, надішли коректний URL.");
            } elseif (str_contains($e->getMessage(), 'olx.ua')) {
                $this->reply("❌ <b>Невірне посилання</b>\n\nЯ працюю тільки з сайтом <code>olx.ua</code>.");
            } else {
                $this->reply("⚠️ Помилка: " . $e->getMessage());
            }
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Show a list of all monitors for the current user.
     * Command: /list
     */
    public function list(): void
    {
        $user = $this->registerUser();
        $monitors = GetUserMonitorsAction::run($user);

        if ($monitors->isEmpty()) {
            $this->reply("📭 <b>У тебе поки немає активних моніторів.</b>\n\nНадішли мені посилання з OLX, щоб додати перше!");
            return;
        }

        $this->reply("📋 <b>Твої монітори:</b>");

        foreach ($monitors as $monitor) {
            $statusIcon = $monitor->is_active ? '🟢 Active' : 'zzz Paused';
            $title = htmlspecialchars($monitor->name ?? $monitor->url);

            $message = "<b>ID: {$monitor->id}</b> | {$statusIcon}\n";
            $message .= "🔗 <a href='{$monitor->url}'>{$title}</a>\n";
            $message .= "<i>Перевірено: " . ($monitor->last_checked_at?->diffForHumans() ?? 'Never') . "</i>";

            // Dynamic toggle button text
            $toggleBtnText = $monitor->is_active ? '⏸ Pause' : '▶️ Resume';
            $toggleAction = $monitor->is_active ? 'pause' : 'resume';

            $keyboard = Keyboard::make()->buttons([
                Button::make($toggleBtnText)->action($toggleAction)->param('id', $monitor->id),
                Button::make('🗑 Delete')->action('delete')->param('id', $monitor->id),
            ]);

            $this->chat->html($message)->keyboard($keyboard)->send();
        }
    }

    /**
     * Action to delete a monitor.
     */
    public function delete(string $id): void
    {
        try {
            DeleteMonitorAction::run($this->registerUser(), $id);
            $this->reply("✅ Монітор <b>#{$id}</b> успішно видалено.");
        } catch (ModelNotFoundException) {
            $this->reply("❌ Монітор не знайдено або він тобі не належить.");
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Action to pause a monitor.
     */
    public function pause(string $id): void
    {
        $this->toggleStatus($id, false);
    }

    /**
     * Action to resume a monitor.
     */
    public function resume(string $id): void
    {
        $this->toggleStatus($id, true);
    }

    /**
     * Shared logic for toggling status.
     */
    protected function toggleStatus(string $id, bool $status): void
    {
        try {
            ToggleMonitorStatusAction::run($this->registerUser(), $id, $status);

            $text = $status ? 'відновлено ▶️' : 'зупинено ⏸';
            $this->reply("✅ Роботу монітора <b>#{$id}</b> {$text}.");
        } catch (ModelNotFoundException) {
            $this->reply("❌ Монітор не знайдено.");
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Registers or retrieves the user based on Telegram data.
     */
    protected function registerUser(): User
    {
        $name = $this->message?->from()?->firstName() ?? $this->message?->from()?->username();
        return RegisterUserAction::run($this->chat, $name);
    }

    /**
     * Centralized exception handling.
     */
    protected function handleException(Throwable $e): void
    {
        // Handle Limit Exceeded Exception
        if (str_contains(strtolower($e->getMessage()), 'limit')) {
            $this->chat->html("🛑 <b>Ліміт вичерпано!</b>\n\nТи досяг максимальної кількості моніторів.\nВидали старі монітори або очікуй запуску Pro версії.")
                ->keyboard(Keyboard::make()->buttons([
                    Button::make('Мої монітори')->action('list')
                ]))
                ->send();
            return;
        }

        Log::error("Webhook Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $this->reply("⚠️ Сталася помилка: " . $e->getMessage());
    }

    public function registerBotCommands(): void
    {
        $bot = \DefStudio\Telegraph\Models\TelegraphBot::find(1);

        $bot->registerCommands([
            'start' => 'Почати роботу',
            'help' => 'Інструкція',
            'list' => 'Мої монітори (керування)',
            'stats' => 'Статистика використання',
        ])->send();
    }
}
