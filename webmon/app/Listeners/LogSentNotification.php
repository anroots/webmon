<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

/**
 * Whenever a Notification is sent, log it
 *
 * @package App\Listeners
 */
class LogSentNotification
{
    public function handle(NotificationSent $event)
    {
        Log::info('Notification sent', [
            'channel' => $event->channel,
            'to' => $event->notifiable->routeNotificationFor($event->channel),
            'notification_id' => $event->notification->id,
            'response' => $event->response
        ]);
    }
}