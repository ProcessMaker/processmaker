<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
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
        if (Arr::get($this->errorHandling, 'inapp_notification') === true) {
            $via[] = 'broadcast';
            $via[] = NotificationChannel::class;
        }
        if (Arr::get($this->errorHandling, 'email_notification') === true) {
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

        $title = $data['message'];

        return (new MailMessage)
            ->error()
            ->subject($data['name'])
            ->greeting($data['name'])
            ->salutation('')
            ->line($data['message'])
            ->action(__('View Request'), url($data['url']))
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
        $processRequest = $this->tokenInterface->processRequest;

        return [
            'type' => 'ERROR_EXCECUTION',
            'message' => $this->tokenInterface->process->name . ' #' . $processRequest->id . ' - ' . $this->tokenInterface->element_name,
            'name' => __('Execution Error') . ': ' . $processRequest->name,
            'processName' => $processRequest->name,
            'request_id' => $processRequest->id,
            'user_id' => $notifiable->id,
            'dateTime' => $processRequest->updated_at->toIso8601String(),
            'uid' => $processRequest->id,
            'url' => '/requests/' . $processRequest->id,
        ];
    }
}
