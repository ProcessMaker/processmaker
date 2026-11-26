<?php

namespace Tests\Feature\Auth;

use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Passport;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * FOUR-34533: an anonymous Web Entry session must never be able to use the
 * Passport API cookie (laravel_token) to reach `auth:api` endpoints as the
 * shared `_pm4_anon_user` system account.
 */
class AnonymousApiTokenTest extends TestCase
{
    /**
     * Build the same cookies/headers a browser sends from a page that received
     * a `laravel_token` for $userId: a real session (LaravelTokenMiddleware
     * validates the session CSRF), the Passport JWT cookie and X-CSRF-TOKEN.
     */
    private function asBrowserWithApiCookieFor(int $userId): static
    {
        $session = app('session')->driver();
        $session->start();
        $csrf = $session->token();
        $session->save();

        $jwt = app(ApiTokenCookieFactory::class)->make($userId, $csrf)->getValue();

        return $this->withCookie(config('session.cookie'), $session->getId())
            ->withCookie(Passport::cookie(), $jwt)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => $csrf,
                'X-Requested-With' => 'XMLHttpRequest',
            ]);
    }

    public function test_anonymous_user_api_cookie_is_rejected_on_auth_api_routes(): void
    {
        $anonymous = app(AnonymousUser::class);
        ProcessRequest::factory()->create(['user_id' => $anonymous->id]);

        foreach (['/api/1.0/requests', '/api/1.0/tasks', '/api/1.0/screens'] as $url) {
            $this->asBrowserWithApiCookieFor($anonymous->id)
                ->get($url)
                ->assertStatus(401);
        }
    }

    public function test_anonymous_user_api_cookie_cannot_delete_anonymous_requests(): void
    {
        $anonymous = app(AnonymousUser::class);
        $request = ProcessRequest::factory()->create(['user_id' => $anonymous->id]);

        $this->asBrowserWithApiCookieFor($anonymous->id)
            ->delete('/api/1.0/requests/' . $request->id)
            ->assertStatus(401);

        $this->assertNotNull(ProcessRequest::find($request->id));
    }

    public function test_regular_user_still_receives_api_cookie_on_web_pages(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);

        $this->actingAs($user, 'web')
            ->get(route('about.index'))
            ->assertStatus(200)
            ->assertCookie(Passport::cookie());
    }

    public function test_regular_user_api_cookie_still_works(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);

        $this->asBrowserWithApiCookieFor($user->id)
            ->get('/api/1.0/requests')
            ->assertStatus(200);
    }
}
