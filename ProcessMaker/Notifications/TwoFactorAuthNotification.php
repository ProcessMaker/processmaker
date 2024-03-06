<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ProcessMaker\Models\User;

class TwoFactorAuthNotification extends Notification
{
    use Queueable;

    private $user;

    private $code;

    /**
     * Create a new notification instance.
     *
     * @param \ProcessMaker\Models\User $user
     * @param string $code
     */
    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(_('Security Code'))
            ->greeting($this->user->username)
            ->line(__('This is your security code: :code', ['code' => $this->code]));
    }

    /**
     * Get the database representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'username' => $this->user->username,
            'code' => $this->code,
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
