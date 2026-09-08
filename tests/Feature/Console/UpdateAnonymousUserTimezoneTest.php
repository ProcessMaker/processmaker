<?php

namespace Tests\Feature\Console;

use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\User;
use Tests\TestCase;

class UpdateAnonymousUserTimezoneTest extends TestCase
{
    public function testUpdatesTimezoneFromConfig(): void
    {
        $user = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $user->timezone = 'America/Chicago';
        $user->save();

        config(['app.anonymous_user_timezone' => 'America/New_York']);

        $this->artisan('processmaker:update-anonymous-user-timezone')
            ->expectsOutput('Anonymous user timezone updated from [America/Chicago] to [America/New_York].')
            ->assertExitCode(0);

        $this->assertEquals('America/New_York', $user->fresh()->timezone);
    }

    public function testDoesNothingWhenTimezoneAlreadyMatches(): void
    {
        $user = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $user->timezone = 'UTC';
        $user->save();

        config(['app.anonymous_user_timezone' => 'UTC']);

        $this->artisan('processmaker:update-anonymous-user-timezone')
            ->expectsOutput('Anonymous user timezone is already set to [UTC].')
            ->assertExitCode(0);

        $this->assertEquals('UTC', $user->fresh()->timezone);
    }

    public function testUpdatesTimezoneFromOption(): void
    {
        $user = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $user->timezone = 'UTC';
        $user->save();

        config(['app.anonymous_user_timezone' => 'UTC']);

        $this->artisan('processmaker:update-anonymous-user-timezone', [
            '--timezone' => 'Europe/Madrid',
        ])
            ->expectsOutput('Anonymous user timezone updated from [UTC] to [Europe/Madrid].')
            ->assertExitCode(0);

        $this->assertEquals('Europe/Madrid', $user->fresh()->timezone);
    }

    public function testCanBeRunMultipleTimes(): void
    {
        $user = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $user->timezone = 'America/Chicago';
        $user->save();

        config(['app.anonymous_user_timezone' => 'America/Los_Angeles']);

        $this->artisan('processmaker:update-anonymous-user-timezone')->assertExitCode(0);
        $this->artisan('processmaker:update-anonymous-user-timezone')
            ->expectsOutput('Anonymous user timezone is already set to [America/Los_Angeles].')
            ->assertExitCode(0);

        $this->assertEquals('America/Los_Angeles', $user->fresh()->timezone);
    }

    public function testFailsWhenAnonymousUserDoesNotExist(): void
    {
        User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->forceDelete();

        $this->artisan('processmaker:update-anonymous-user-timezone')
            ->expectsOutput('Anonymous user not found.')
            ->assertExitCode(1);
    }
}
