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
        $this->processUid = $token->processRequest->process->uuid_text;
        $this->instanceUid = $token->processRequest->uuid_text;
        $this->tokenUid = $token->uuid_text;
        $this->tokenElement = $token->element_uuid;
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
        return ['broadcast'];
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
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function toBroadcast($notifiable)
    {
        $process = Process::withUuid($this->processUid)->first();
        $definitions = $process->getDefinitions();
        $activity = $definitions->getActivity($this->tokenElement);
        $token = Token::withUuid($this->tokenUid)->first();
        return new BroadcastMessage([
            'message' => sprintf('Task created: %s', $activity->getName()),
            'name' => $activity->getName(),
            'processName' => $process->name,
            'userName' => $token->user->getFullName(),
            'dateTime' => $token->created_at->toIso8601String(),
            'uid' => $this->tokenUid,
            'url' => sprintf(
                '/tasks/%s/%s/%s/%s',
                $this->tokenElement,
                $this->processUid,
                $this->instanceUid,
                $this->tokenUid
            ),
        ]);

    }

}
