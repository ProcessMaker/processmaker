<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ProcessMaker\Models\ProcessRequest as Instance;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class ProcessCompletedNotification extends Notification
{
    use Queueable;

    private $processUid;
    private $processName;
    private $instanceUid;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ExecutionInstanceInterface $instance)
    {
        $this->processUid = $instance->process->getKey();
        $this->processName = $instance->process->name;
        $this->instanceUid = $instance->getKey();
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
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

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
        $instance = Instance::find($this->instanceUid);
        return [
            'type' => 'PROCESS_COMPLETED',
            'name' => sprintf('Request completed: %s', $this->processName),
            'dateTime' => $instance->completed_at->toIso8601String(),
            'uid' => $this->processName,
            'request_id' => $instance->getKey(),
            'url' => sprintf(
                '/requests/%s',
                $this->instanceUid
            )
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

}
