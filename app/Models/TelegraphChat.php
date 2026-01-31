<?php

declare(strict_types=1);

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphChat as BaseTelegraphChat;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chat_id
 * @property string $name
 * @property int $user_id
 * @property-read User $user
 * @property int $telegraph_bot_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \DefStudio\Telegraph\Models\TelegraphBot $bot
 * @method static \DefStudio\Telegraph\Database\Factories\TelegraphChatFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereTelegraphBotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelegraphChat whereUserId($value)
 * @mixin \Eloquent
 */
class TelegraphChat extends BaseTelegraphChat
{
    /**
     * @var list<string> The attributes that are mass assignable.
     */
    protected $fillable = [
        'chat_id',
        'name',
        'user_id'
    ];

    /**
     * Get the user that owns the chat.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
