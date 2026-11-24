<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use Illuminate\Validation\ValidationException;
use ProcessMaker\Models\Process;

/**
 * Request notification settings of a process, written the way the
 * `PUT /api/1.0/processes/{id}` endpoint does (`notifications` field): a map of
 * notifiable type (requester, assignee, participants, manager) to notification
 * type (started, canceled, completed, error, comment) to a boolean.
 *
 * Only the pairs that are sent change; pairs that are not sent keep their value.
 */
class ProcessNotificationSettings
{
    /**
     * Reject notifiable or notification types the process does not know, so a
     * typo is an error and not a silent no-op.
     *
     * @param array<string, mixed> $notifications
     * @throws ValidationException
     */
    public function assertKnown(array $notifications): void
    {
        $process = new Process();
        $errors = [];

        foreach ($notifications as $notifiable => $types) {
            if (!in_array($notifiable, $process->requestNotifiableTypes, true) || !is_array($types)) {
                $errors['notifications'][] = sprintf(
                    'Unknown notifiable type "%s". Use: %s.',
                    (string) $notifiable,
                    implode(', ', $process->requestNotifiableTypes),
                );

                continue;
            }

            foreach (array_keys($types) as $type) {
                if (!in_array($type, $process->requestNotificationTypes, true)) {
                    $errors['notifications'][] = sprintf(
                        'Unknown notification type "%s" for %s. Use: %s.',
                        (string) $type,
                        $notifiable,
                        implode(', ', $process->requestNotificationTypes),
                    );
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Turn the sent notifications on or off. Call {@see assertKnown()} first.
     *
     * @param array<string, array<string, mixed>> $notifications
     */
    public function apply(Process $process, array $notifications): void
    {
        foreach ($notifications as $notifiable => $types) {
            foreach ($types as $type => $wanted) {
                if ($wanted === null) {
                    continue;
                }
                $this->set($process, (string) $notifiable, (string) $type, filter_var($wanted, FILTER_VALIDATE_BOOLEAN));
            }
        }
    }

    private function set(Process $process, string $notifiable, string $type, bool $wanted): void
    {
        $keys = ['element_id' => null, 'notifiable_type' => $notifiable, 'notification_type' => $type];

        if ($wanted) {
            $process->notification_settings()->firstOrCreate($keys);

            return;
        }

        $process->notification_settings()
            ->whereNull('element_id')
            ->where('notifiable_type', $notifiable)
            ->where('notification_type', $type)
            ->delete();
    }
}
