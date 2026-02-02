<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Notification;

use App\Models\TelegraphChat;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Models\Listing;

/**
 * Action to send a notification about a new listing.
 *
 * @package Modules\Scraper\Actions\Notification
 */
class SendNewListingNotificationAction
{
    use AsAction;

    /**
     * Send a new listing notification to the given Telegraph chat.
     *
     * @param TelegraphChat $chat
     * @param Listing $listing
     *
     * @return void
     */
    public function handle(TelegraphChat $chat, Listing $listing): void
    {
        $price = $listing->price
            ? number_format((float)$listing->price, 0, '.', ' ') . ' грн'
            : 'Price not specified';

        $message = "🚨 <b>New listing found!</b>\n\n";
        $message .= "📦 <b>{$listing->title}</b>\n";
        $message .= "💰 <b>{$price}</b>\n\n";
        $message .= "<i>Found: {$listing->created_at->format('H:i')}</i>";

        $keyboard = Keyboard::make()->buttons([
            Button::make('🔗 Переглянути на OLX')->url($listing->url),
        ]);

        if ($listing->image_url) {
            $chat->photo($listing->image_url)->html($message)->keyboard($keyboard)->send();
        } else {
            $chat->html($message)->keyboard($keyboard)->send();
        }
    }
}
