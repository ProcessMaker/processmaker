<?php

namespace Tests\unit\ProcessMaker\Broadcasting;

use ProcessMaker\Broadcasting\ValidatesPusherCredentials;
use RuntimeException;
use Tests\TestCase;

class ValidatesPusherCredentialsTest extends TestCase
{
    public function testSkipsValidationWhenBroadcastDriverIsNotPusher(): void
    {
        config(['broadcasting.default' => 'log']);

        ValidatesPusherCredentials::validate();

        $this->addToAssertionCount(1);
    }

    public function testAllowsInsecureDefaultsInNonProduction(): void
    {
        config([
            'app.env' => 'local',
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'app_id' => ValidatesPusherCredentials::INSECURE_APP_ID,
                'key' => ValidatesPusherCredentials::INSECURE_APP_KEY,
                'secret' => ValidatesPusherCredentials::INSECURE_APP_SECRET,
            ],
        ]);

        ValidatesPusherCredentials::validate();

        $this->addToAssertionCount(1);
    }

    public function testRejectsInsecureDefaultsInProduction(): void
    {
        config([
            'app.env' => 'production',
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'app_id' => ValidatesPusherCredentials::INSECURE_APP_ID,
                'key' => ValidatesPusherCredentials::INSECURE_APP_KEY,
                'secret' => ValidatesPusherCredentials::INSECURE_APP_SECRET,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PUSHER_APP_ID');

        ValidatesPusherCredentials::validate();
    }

    public function testAllowsInsecureDefaultsWhenExplicitlyEnabled(): void
    {
        config([
            'app.env' => 'production',
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher' => [
                'app_id' => ValidatesPusherCredentials::INSECURE_APP_ID,
                'key' => ValidatesPusherCredentials::INSECURE_APP_KEY,
                'secret' => ValidatesPusherCredentials::INSECURE_APP_SECRET,
            ],
        ]);

        putenv('PUSHER_ALLOW_INSECURE_DEFAULTS=true');

        try {
            ValidatesPusherCredentials::validate();
            $this->addToAssertionCount(1);
        } finally {
            putenv('PUSHER_ALLOW_INSECURE_DEFAULTS');
        }
    }
}
