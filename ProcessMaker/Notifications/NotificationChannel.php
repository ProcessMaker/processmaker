<?php

namespace ProcessMaker\Notifications;

use Illuminate\Notifications\Notification;

class NotificationChannel extends Notification
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'notifiable_id' => $notifiable->getKey(),
            'notifiable_type' => get_class($notifiable),
            'data' => $data,
            'url' => $data['url'],
            'read_at' => null,
        ]);
    }
}
