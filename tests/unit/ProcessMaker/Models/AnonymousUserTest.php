<?php

namespace Tests\Unit\ProcessMaker\Models;

use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\User;
use Tests\TestCase;

class AnonymousUserTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->forgetScopedInstances();

        parent::tearDown();
    }

    public function test_resolve_returns_anonymous_user_from_database(): void
    {
        $user = AnonymousUser::resolve();

        $this->assertInstanceOf(AnonymousUser::class, $user);
        $this->assertSame(AnonymousUser::ANONYMOUS_USERNAME, $user->username);
    }

    public function test_container_binding_returns_same_instance_within_request(): void
    {
        $first = app(AnonymousUser::class);
        $second = app(AnonymousUser::class);

        $this->assertSame($first, $second);
    }

    public function test_container_binding_is_not_reused_across_requests(): void
    {
        $first = app(AnonymousUser::class);

        $this->app->forgetScopedInstances();

        $second = app(AnonymousUser::class);

        $this->assertNotSame($first, $second);
        $this->assertSame($first->id, $second->id);
    }

    public function test_container_binding_reflects_database_changes_after_flush(): void
    {
        $original = app(AnonymousUser::class);
        $originalEmail = $original->email;

        User::where('username', AnonymousUser::ANONYMOUS_USERNAME)
            ->update(['email' => 'updated-anon@example.com']);

        $this->app->forgetScopedInstances();

        $refreshed = app(AnonymousUser::class);

        $this->assertSame('updated-anon@example.com', $refreshed->email);
        $this->assertNotSame($originalEmail, $refreshed->email);

        User::where('username', AnonymousUser::ANONYMOUS_USERNAME)
            ->update(['email' => $originalEmail]);
    }
}
