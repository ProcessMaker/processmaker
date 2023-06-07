<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ProcessTranslationReady extends Notification
{
    use Queueable;

    private $code;

    private $process;

    private $targetLanguage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($code, $process, $targetLanguage)
    {
        $this->code = $code;
        $this->process = $process;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['broadcast', 'database'];
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
            'code' => $this->code,
            'name' => __('Process translated'),
            'processId' => $this->process->id ?? '',
            'processName' => $this->process->name ?? '',
            'targetLanguage' => $this->targetLanguage ?? '',
        ];
    }

    /**
     * To broadcast.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the type of the notification being broadcast.
     *
     * @return string
     */
    public function broadcastType()
    {
        return str_replace('\\', '.', static::class);
    }
}
