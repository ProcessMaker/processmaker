<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class FileReadyNotification extends Notification
{
    use Queueable;

    private $url;
    private $name;
    private $fileType;
    private $fileId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($url, $name, $fileType, $fileId)
    {
        $this->url = $url;
        $this->name = $name;
        $this->fileType = $fileType;
        $this->fileId = $fileId;
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
     * Get the database representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    /*
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'FILE_READY' ,
            'message' => __("The :file you requested is now ready for download.", ['file' => $this->name]),
            'name' => __("The :file you requested is now ready for download.", ['file' => $this->name]),
            'url' => $this->url,
            'fileType' => $this->fileType,
            'fileId' => $this->fileId,
        ];
    }

    /*
     * Get the broadcast representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

}
