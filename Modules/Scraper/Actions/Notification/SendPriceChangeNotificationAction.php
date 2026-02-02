<?php

declare(strict_types=1);

namespace Modules\Scraper\Actions\Notification;

use App\Models\TelegraphChat;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Scraper\Models\Listing;

/**
 * Action to send a notification about a price change in a listing.
 *
 * @package Modules\Scraper\Actions\Notification
 */
class SendPriceChangeNotificationAction
{
    use AsAction;

    /**
     * Send a price change notification to the given Telegraph chat.
     *
     * @param TelegraphChat $chat
     * @param Listing $listing
     * @param float $oldPrice
     * @param float $newPrice
     *
     * @return void
     */
    public function handle(TelegraphChat $chat, Listing $listing, float $oldPrice, float $newPrice): void
    {
        $diff = $newPrice - $oldPrice;
        $icon = $diff < 0 ? '📉' : '📈';
        $action = $diff < 0 ? 'Ціна впала!' : 'Ціна зросла!';

        $oldPriceFmt = number_format($oldPrice, 0, '.', ' ');
        $newPriceFmt = number_format($newPrice, 0, '.', ' ');
        $diffFmt = number_format(abs($diff), 0, '.', ' ');

        $message = "{$icon} <b>{$action}</b>\n\n";
        $message .= "📦 <b>{$listing->title}</b>\n";
        $message .= "<s>{$oldPriceFmt} грн</s>  ➡️  <b>{$newPriceFmt} грн</b>\n";
        $message .= "<i>Різниця: " . ($diff < 0 ? '-' : '+') . "{$diffFmt} грн</i>";

        $keyboard = Keyboard::make()->buttons([
            Button::make('🔗 Переглянути на OLX')->url($listing->url),
        ]);

        $chat->html($message)->keyboard($keyboard)->send();
    }
}
