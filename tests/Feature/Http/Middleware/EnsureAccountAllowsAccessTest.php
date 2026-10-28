<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Http\Middleware\EnsureAccountAllowsAccess;
use ProcessMaker\Http\Middleware\ProcessMakerAuthenticate;
use ProcessMaker\Models\User;
use Tests\TestCase;

class EnsureAccountAllowsAccessTest extends TestCase
{
    public function test_web_middleware_does_not_resolve_the_api_guard_for_a_malformed_bearer_token(): void
    {
        $user = new User(['status' => 'ACTIVE']);
        $webGuard = Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(true);
        $webGuard->shouldReceive('user')->once()->andReturn($user);

        Auth::shouldReceive('guard')->with('web')->andReturn($webGuard);
        Auth::shouldReceive('guard')->never()->with('api');

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer undefined',
        ]);

        $response = (new EnsureAccountAllowsAccess())->handle($request, fn () => response('allowed'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    #[DataProvider('deniedWebStatuses')]
    public function test_web_middleware_denies_users_with_a_disallowed_status(string $status): void
    {
        $user = new User(['status' => $status]);
        $webGuard = Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(true);
        $webGuard->shouldReceive('user')->once()->andReturn($user);
        $webGuard->shouldReceive('logout')->once();

        Auth::shouldReceive('guard')->with('web')->andReturn($webGuard);
        Auth::shouldReceive('guard')->never()->with('api');

        $response = (new EnsureAccountAllowsAccess())->handle(Request::create('/', 'GET'), fn () => response('allowed'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/login', $response->headers->get('Location'));
    }

    public static function deniedWebStatuses(): array
    {
        return [
            'blocked' => ['BLOCKED'],
            'inactive' => ['INACTIVE'],
        ];
    }

    public function test_api_account_check_does_not_resolve_the_web_guard_for_an_active_user(): void
    {
        $user = new User(['status' => 'ACTIVE']);
        $apiGuard = Mockery::mock();
        $apiGuard->shouldReceive('check')->once()->andReturn(true);
        $apiGuard->shouldReceive('user')->once()->andReturn($user);

        Auth::shouldReceive('guard')->never()->with('web');
        Auth::shouldReceive('guard')->with('api')->andReturn($apiGuard);

        $response = EnsureAccountAllowsAccess::blockingResponseForRequest(
            Request::create('/api/test', 'GET'),
            ['api']
        );

        $this->assertNull($response);
    }

    #[DataProvider('deniedApiStatuses')]
    public function test_api_account_check_denies_users_with_a_disallowed_status(string $status, string $message): void
    {
        $user = new User(['status' => $status]);
        $apiGuard = Mockery::mock();
        $apiGuard->shouldReceive('check')->once()->andReturn(true);
        $apiGuard->shouldReceive('user')->once()->andReturn($user);
        $apiGuard->shouldReceive('logout')->once();

        Auth::shouldReceive('guard')->never()->with('web');
        Auth::shouldReceive('guard')->with('api')->andReturn($apiGuard);

        $response = EnsureAccountAllowsAccess::blockingResponseForRequest(
            Request::create('/api/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']),
            ['api']
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['message' => $message], json_decode($response->getContent(), true));
    }

    public static function deniedApiStatuses(): array
    {
        return [
            'blocked' => ['BLOCKED', 'Account locked after too many failed attempts. Contact administrator.'],
            'inactive' => ['INACTIVE', 'Unauthorized'],
        ];
    }

    public function test_processmaker_authenticate_checks_only_the_authenticated_api_guard(): void
    {
        $user = new User(['status' => 'INACTIVE']);
        $apiGuard = Mockery::mock();
        $apiGuard->shouldReceive('check')->once()->andReturn(true);
        $apiGuard->shouldReceive('user')->once()->andReturn($user);
        $apiGuard->shouldReceive('logout')->once();

        Auth::shouldReceive('guard')->never()->with('web');
        Auth::shouldReceive('guard')->with('api')->andReturn($apiGuard);

        $middleware = new class(app('auth')) extends ProcessMakerAuthenticate {
            protected function authenticate($request, array $guards)
            {
                // Authentication is outside the scope of this guard-selection test.
            }
        };

        $response = $middleware->handle(
            Request::create('/api/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']),
            fn () => response('allowed'),
            'api'
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['message' => 'Unauthorized'], json_decode($response->getContent(), true));
    }
}
