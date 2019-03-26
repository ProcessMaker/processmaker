<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ScriptResponseNotification extends Notification
{
    use Queueable;

    protected $status;
    protected $response;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($status, array $response)
    {
        $this->status = $status;
        $this->response = $response;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $date = new Carbon();
        return [
            'type' => 'SCRIPT_RESPONSE',
            'name' => __('Script executed'),
            'dateTime' => $date->toIso8601String(),
            'status' => $this->status,
            'response' => $this->response,
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
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the value of response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
