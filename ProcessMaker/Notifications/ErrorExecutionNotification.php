<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ErrorExecutionNotification extends Notification
{
    use Queueable;

    private $tokenInterface;
    private $message;
    private $errorHandling;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(TokenInterface $tokenInterface, $message = '', $errorHandling = [])
    {
        $this->tokenInterface = $tokenInterface;
        $this->message = $message;
        $this->errorHandling = $errorHandling;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = [];
        if ($this->errorHandling['inapp_notification'] === true) {
            $via[] = NotificationChannel::class;
        }
        if ($this->errorHandling['email_notification'] === true) {
            $via[] = 'mail';
        }
        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $data = $this->toArray($notifiable);
        return (new MailMessage)
            ->line($data['message'])
            ->action('Notification Action', url($data['url']))
            ->line($this->message);
    }

    /**
     * Get the broadcast representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'ERROR_EXCECUTION',
            'message' => sprintf('Error excecution: %s', $this->tokenInterface->process->name),
            'name' => $this->tokenInterface->process->name,
            'processName' => $this->tokenInterface->process->name,
            'request_id' => $this->tokenInterface->processRequest->id,
            'user_id' => $this->tokenInterface->processRequest->user_id,
            'dateTime' => $this->tokenInterface->processRequest->created_at->toIso8601String(),
            'uid' => $this->tokenInterface->getKey(),
            'url' => sprintf(
                '/tasks/%s/edit',
                $this->tokenInterface->id
            ),
        ];
    }
}