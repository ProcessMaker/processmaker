<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Multitenancy\Broadcasting;

use Illuminate\Http\Request;
use Mockery;
use ProcessMaker\Models\User;
use ProcessMaker\Multitenancy\Broadcasting\TenantAwarePusherBroadcaster;
use Pusher\Pusher;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class TenantAwarePusherBroadcasterTest extends TestCase
{
    public function test_auth_accepts_tenant_prefixed_private_channel(): void
    {
        $user = User::factory()->create();
        $this->setCurrentTenantId(4);

        $pusher = Mockery::mock(Pusher::class);
        $pusher->shouldReceive('authorizeChannel')
            ->once()
            ->with('private-tenant_4.ProcessMaker.Models.User.' . $user->id, '1.234')
            ->andReturn(json_encode(['auth' => 'app-key:signature']));

        $broadcaster = $this->broadcasterWithUserChannel($pusher);

        $response = $broadcaster->auth($this->authRequest(
            'private-tenant_4.ProcessMaker.Models.User.' . $user->id,
            $user
        ));

        $this->assertSame(['auth' => 'app-key:signature'], $response);
    }

    public function test_auth_rejects_channel_for_a_different_tenant(): void
    {
        $user = User::factory()->create();
        $this->setCurrentTenantId(4);

        $broadcaster = $this->broadcasterWithUserChannel(Mockery::mock(Pusher::class));

        $this->expectException(AccessDeniedHttpException::class);

        $broadcaster->auth($this->authRequest(
            'private-tenant_9.ProcessMaker.Models.User.' . $user->id,
            $user
        ));
    }

    public function test_auth_rejects_unprefixed_channel_when_tenant_is_current(): void
    {
        $user = User::factory()->create();
        $this->setCurrentTenantId(4);

        $broadcaster = $this->broadcasterWithUserChannel(Mockery::mock(Pusher::class));

        $this->expectException(AccessDeniedHttpException::class);

        $broadcaster->auth($this->authRequest(
            'private-ProcessMaker.Models.User.' . $user->id,
            $user
        ));
    }

    public function test_format_channels_prefixes_private_and_presence_names(): void
    {
        $this->setCurrentTenantId(4);
        $broadcaster = new TenantAwarePusherBroadcaster(Mockery::mock(Pusher::class));

        $this->assertSame(
            [
                'private-tenant_4.ProcessMaker.Models.User.1',
                'presence-tenant_4.room',
                'tenant_4.open-channel',
            ],
            $this->formatChannels($broadcaster, [
                'private-ProcessMaker.Models.User.1',
                'presence-room',
                'open-channel',
            ])
        );
    }

    public function test_format_channels_does_not_double_prefix(): void
    {
        $this->setCurrentTenantId(4);
        $broadcaster = new TenantAwarePusherBroadcaster(Mockery::mock(Pusher::class));

        $this->assertSame(
            ['private-tenant_4.ProcessMaker.Models.User.1'],
            $this->formatChannels($broadcaster, ['private-tenant_4.ProcessMaker.Models.User.1'])
        );
    }

    private function broadcasterWithUserChannel(Pusher $pusher): TenantAwarePusherBroadcaster
    {
        $broadcaster = new TenantAwarePusherBroadcaster($pusher);
        $broadcaster->channel('ProcessMaker.Models.User.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        return $broadcaster;
    }

    private function authRequest(string $channelName, User $user): Request
    {
        $request = Request::create('/broadcasting/auth', 'POST', [
            'socket_id' => '1.234',
            'channel_name' => $channelName,
        ]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function setCurrentTenantId(int $id): void
    {
        app()->instance('currentTenant', (object) ['id' => $id]);
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    private function formatChannels(TenantAwarePusherBroadcaster $broadcaster, array $channels): array
    {
        $method = new ReflectionMethod($broadcaster, 'formatChannels');

        return $method->invoke($broadcaster, $channels);
    }
}
