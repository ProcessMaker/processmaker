<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Script;

class ScriptResponseNotification extends Notification
{
    use Queueable;

    protected $status;
    protected $response;
    protected $watcher;
    protected $nonce;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($status, array $response, $watcher = null, $nonce = null)
    {
        $this->status = $status;
        $this->response = $response;
        $this->watcher = $watcher;
        $this->nonce = $nonce;
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
        $response = $this->cacheResponse($this->response);
        return [
            'type' => 'SCRIPT_RESPONSE',
            'name' => __('Script executed'),
            'dateTime' => $date->toIso8601String(),
            'status' => $this->status,
            'watcher' => $this->watcher,
            'response' => $response,
            'nonce' => $this->nonce,
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
    
    /**
     * Get the value of nonce
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Cache the script response to be loaded by API
     *
     * @return string
     */
    public function cacheResponse()
    {
        $key = uniqid('srn', true);
        Cache::put("srn.$key", $this->response, now()->addMinutes(1));
        return ['key' => $key];
    }
}
