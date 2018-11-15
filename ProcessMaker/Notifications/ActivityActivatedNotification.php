<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ActivityActivatedNotification extends Notification
{
    use Queueable;

    private $processUid;
    private $instanceUid;
    private $tokenUid;
    private $tokenElement;
    private $tokenStatus;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(TokenInterface $token)
    {
        $this->processUid = $token->processRequest->process->getKey();
        $this->instanceUid = $token->processRequest->getKey();
        $this->tokenUid = $token->getKey();
        $this->tokenElement = $token->element_id;
        $this->tokenStatus = $token->status;
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
        $process = Process::find($this->processUid);
        $definitions = $process->getDefinitions();
        $activity = $definitions->getActivity($this->tokenElement);
        $token = Token::find($this->tokenUid);
        $request = $token->processRequest;
        return [
            'message' => sprintf('Task created: %s', $activity->getName()),
            'name' => $activity->getName(),
            'processName' => $process->name,
            'request_id' => $request->getKey(),
            'userName' => $token->user->getFullName(),
            'dateTime' => $token->created_at->toIso8601String(),
            'uid' => $this->tokenUid,
            'url' => sprintf(
                '/tasks/%s/edit',
                $this->tokenUid
            )
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
