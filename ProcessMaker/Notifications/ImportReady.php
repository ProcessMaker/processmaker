<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ImportReady extends Notification
{
    use Queueable;

    /**
     * Importing code
     *
     * @var string
     */
    private $code;

    /**
     * Response of the import
     *
     * @var array
     */
    private $data = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($code, array $data = [])
    {
        $this->code = $code;
        $this->data = $data;
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
            'data' => $this->data,
            'name' => __('Process imported'),
            'processName' => $this->data['process']['name'],
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
