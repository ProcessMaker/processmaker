<?php

namespace ProcessMaker\Notifications;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class DatasourceResponseNotification extends Notification
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
     * To broadcast.
     *
     * @param $notifiable
     * @return BroadcastMessage
     * @throws Exception
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array
     * @throws Exception
     */
    public function toArray()
    {
        $date = new Carbon();
        return [
            'type' => 'DATASOURCE_RESPONSE',
            'name' => __('Data Source Executed'),
            'dateTime' => $date->toIso8601String(),
            'status' => $this->status,
            'response' => $this->response,
        ];
    }

}
