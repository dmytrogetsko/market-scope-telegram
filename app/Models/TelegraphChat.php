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
