<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\User;

use App\Models\TelegraphChat;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Str;

/**
 * Action to register a user via Telegram chat.
 *
 * @package Modules\Scraper\Actions\User
 */
class RegisterUserAction
{
    use AsAction;

    /**
     * Finds/Creates user and links them to the Telegram chat.
     *
     * @param TelegraphChat $chat The Telegram chat instance.
     * @param string $username The username to assign if creating a new user.
     *
     * @return User
     */
    public function handle(TelegraphChat $chat, string $username = 'Telegram User'): User
    {
        if ($chat->user_id) {
            return User::find($chat->user_id);
        }

        $user = User::create([
            'name' => $username,
            'email' => $chat->chat_id . '@telegraph.bot', // Unique dummy email
            'password' => bcrypt(Str::random(32)),
            'plan' => 'free',
            'monitor_limit' => 3
        ]);

        // 3. Link the chat to the new user
        $chat->user_id = $user->id;
        $chat->save();

        return $user;
    }
}
