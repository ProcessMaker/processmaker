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

    protected $index;
    protected $status;
    protected $response;

    /**
     * DatasourceResponseNotification constructor.
     *
     * @param $status
     * @param array $response
     * @param $index
     */
    public function __construct($status, array $response, $index)
    {
        $this->index = $index;
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
            'index' => $this->index,
            'type' => 'DATASOURCE_RESPONSE',
            'name' => __('Data Source Executed'),
            'dateTime' => $date->toIso8601String(),
            'status' => $this->status,
            'response' => $this->response,
        ];
    }

}
