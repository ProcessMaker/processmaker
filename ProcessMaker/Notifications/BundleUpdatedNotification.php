<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ProcessMaker\Models\Bundle;

class BundleUpdatedNotification extends Notification
{
    use Queueable;

    private $bundleName;

    private $bundleUid;

    /**
     * Create a new notification instance.
     */
    public function __construct(Bundle $bundle)
    {
        $this->bundleName = $bundle->name;
        $this->bundleUid = $bundle->id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', NotificationChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        $bundle = Bundle::find($this->bundleUid);

        return [
            'type' => 'BUNDLE_UPDATED',
            'message' => sprintf('Bundle updated: %s', $this->bundleName),
            'dateTime' => $bundle->updated_at->toIso8601String(),
            'name' => $this->bundleName,
            'bundleName' => $this->bundleName,
            'url' => sprintf(
                '/admin/devlink/local-bundles/%s/',
                $this->bundleUid
            ),
        ];
    }

    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
