<?php

namespace ProcessMaker\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CaseRetentionLogExportNotification extends Notification
{
    use Queueable;

    public function __construct(
        private bool $success,
        private string $message,
        private ?string $downloadUrl = null,
    ) {
    }

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['broadcast', NotificationChannel::class];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toDatabase($notifiable)
    {
        return $this->payloadData();
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->payloadData());
    }

    /**
     * Full payload shape for Echo + bell (matches API notification resource).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith()
    {
        $now = now()->toIso8601String();

        return [
            'id' => (string) $this->id,
            'type' => self::class,
            'data' => $this->payloadData(),
            'read_at' => null,
            'url' => $this->downloadUrl,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadData(): array
    {
        return [
            'type' => $this->success ? 'CASE_RETENTION_LOG_EXPORT_READY' : 'CASE_RETENTION_LOG_EXPORT_FAILED',
            'message' => $this->message,
            'name' => $this->success ? __('Case retention logs') : $this->message,
            'url' => $this->downloadUrl,
            'dateTime' => now()->toIso8601String(),
        ];
    }

    public function broadcastType()
    {
        return str_replace('\\', '.', self::class);
    }
}
