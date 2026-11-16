<?php

namespace ProcessMaker\Broadcasting;

use RuntimeException;

/**
 * Reject known-default Pusher credentials when broadcasting uses the pusher driver in production.
 */
class ValidatesPusherCredentials
{
    public const INSECURE_APP_ID = 'app-id';

    public const INSECURE_APP_KEY = 'app-key';

    public const INSECURE_APP_SECRET = 'app-secret';

    /**
     * @var list<string>
     */
    private const INSECURE_VALUES = [
        self::INSECURE_APP_ID,
        self::INSECURE_APP_KEY,
        self::INSECURE_APP_SECRET,
        '',
    ];

    public static function validate(): void
    {
        if (config('broadcasting.default') !== 'pusher') {
            return;
        }

        if (config('app.env') !== 'production') {
            return;
        }

        if (filter_var(env('PUSHER_ALLOW_INSECURE_DEFAULTS', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $connection = config('broadcasting.connections.pusher');

        $credentials = [
            'PUSHER_APP_ID' => $connection['app_id'] ?? null,
            'PUSHER_APP_KEY' => $connection['key'] ?? null,
            'PUSHER_APP_SECRET' => $connection['secret'] ?? null,
        ];

        foreach ($credentials as $name => $value) {
            if ($value === null || in_array((string) $value, self::INSECURE_VALUES, true)) {
                throw new RuntimeException(
                    "{$name} must be set to a unique value when BROADCAST_DRIVER=pusher in production. "
                    . 'Set PUSHER_ALLOW_INSECURE_DEFAULTS=true only for local development.'
                );
            }
        }
    }
}
