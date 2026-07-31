<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Mockery;
use ProcessMaker\Events\ActivityCompleted;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Listeners\HandleActivityCompletedRedirect;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Services\RedirectToEventService;
use RuntimeException;
use Tests\TestCase;

class RedirectToEventServiceTest extends TestCase
{
    public function test_only_latest_pending_redirect_is_sent_once(): void
    {
        Event::fake([RedirectToEvent::class]);

        $firstRequest = ProcessRequest::factory()->create();
        $secondRequest = ProcessRequest::factory()->create();
        $service = app(RedirectToEventService::class);

        $service->setRedirectTo($firstRequest, 'firstRedirect', [
            'requestId' => $firstRequest->id,
        ]);
        $service->setRedirectTo($secondRequest, 'secondRedirect', [
            'requestId' => $secondRequest->id,
        ]);

        $service->sendRedirectToEvent();
        $service->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use ($secondRequest) {
            return $event->method === 'secondRedirect'
                && $event->params[0]['requestId'] === $secondRequest->id
                && $event->params['activeTokens'] === []
                && $event->broadcastOn()[0]->name ===
                    'private-ProcessMaker.Models.ProcessRequest.' . $secondRequest->id;
        });
        Event::assertDispatched(RedirectToEvent::class, 1);
    }

    public function test_scoped_binding_does_not_leak_pending_redirect_between_operations(): void
    {
        Event::fake([RedirectToEvent::class]);

        $firstRequest = ProcessRequest::factory()->create();
        $firstScope = app(RedirectToEventService::class);
        $this->assertSame($firstScope, app(RedirectToEventService::class));
        $firstScope->setRedirectTo($firstRequest, 'staleRedirect');

        app()->forgetScopedInstances();

        $secondScope = app(RedirectToEventService::class);
        $this->assertNotSame($firstScope, $secondScope);

        $secondScope->sendRedirectToEvent();
        Event::assertNotDispatched(RedirectToEvent::class);

        $secondRequest = ProcessRequest::factory()->create();
        $secondScope->setRedirectTo($secondRequest, 'currentRedirect', [
            'requestId' => $secondRequest->id,
        ]);
        $secondScope->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use ($secondRequest) {
            return $event->method === 'currentRedirect'
                && $event->params[0]['requestId'] === $secondRequest->id
                && $event->broadcastOn()[0]->name ===
                    'private-ProcessMaker.Models.ProcessRequest.' . $secondRequest->id;
        });
        Event::assertDispatched(RedirectToEvent::class, 1);
    }

    public function test_reset_discards_pending_redirect(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $service->setRedirectTo(ProcessRequest::factory()->create(), 'discardedRedirect');

        $service->reset();
        $service->sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_activity_completed_listener_and_dispatcher_share_the_same_scoped_state(): void
    {
        Event::fake([RedirectToEvent::class]);

        $processRequest = ProcessRequest::factory()->create([
            'process_collaboration_id' => null,
        ]);
        $activeToken = ProcessRequestToken::factory()->create([
            'process_id' => $processRequest->process_id,
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
        ]);
        $activeToken->setInstance($processRequest);

        app(HandleActivityCompletedRedirect::class)->handle(new ActivityCompleted($activeToken));
        app(RedirectToEventService::class)->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use (
            $activeToken,
            $processRequest
        ) {
            return $event->method === 'processUpdated'
                && $event->params[0]['tokenId'] === $activeToken->id
                && $event->params[0]['requestStatus'] === $processRequest->status
                && $event->params['activeTokens'] === [$activeToken->id];
        });
    }

    public function test_active_tokens_exclude_closed_and_unrelated_request_tokens(): void
    {
        Event::fake([RedirectToEvent::class]);

        $processRequest = ProcessRequest::factory()->create([
            'process_collaboration_id' => null,
        ]);
        $unrelatedRequest = ProcessRequest::factory()->create([
            'process_collaboration_id' => null,
        ]);
        $activeToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
        ]);
        $closedToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'CLOSED',
        ]);
        $unrelatedToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $unrelatedRequest->id,
            'status' => 'ACTIVE',
        ]);

        $service = app(RedirectToEventService::class);
        $service->setRedirectTo($processRequest, 'isolatedRedirect');
        $service->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use (
            $activeToken,
            $closedToken,
            $unrelatedToken
        ) {
            return $event->params['activeTokens'] === [$activeToken->id]
                && !in_array($closedToken->id, $event->params['activeTokens'], true)
                && !in_array($unrelatedToken->id, $event->params['activeTokens'], true);
        });
    }

    public function test_active_tokens_include_all_active_tokens_in_the_same_collaboration(): void
    {
        Event::fake([RedirectToEvent::class]);

        $processRequest = ProcessRequest::factory()->create();
        $collaboratingRequest = ProcessRequest::factory()->create([
            'process_collaboration_id' => $processRequest->process_collaboration_id,
        ]);
        $unrelatedRequest = ProcessRequest::factory()->create();
        $firstToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'status' => 'ACTIVE',
        ]);
        $collaboratingToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $collaboratingRequest->id,
            'status' => 'ACTIVE',
        ]);
        $unrelatedToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $unrelatedRequest->id,
            'status' => 'ACTIVE',
        ]);

        $service = app(RedirectToEventService::class);
        $service->setRedirectTo($processRequest, 'collaborationRedirect');
        $service->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use (
            $firstToken,
            $collaboratingToken,
            $unrelatedToken
        ) {
            $activeTokens = $event->params['activeTokens'];
            sort($activeTokens);

            $expectedTokens = [$firstToken->id, $collaboratingToken->id];
            sort($expectedTokens);

            return $activeTokens === $expectedTokens
                && !in_array($unrelatedToken->id, $activeTokens, true);
        });
    }

    public function test_pending_redirect_is_consumed_when_event_dispatch_throws(): void
    {
        $processRequest = ProcessRequest::factory()->create([
            'process_collaboration_id' => null,
        ]);
        $service = app(RedirectToEventService::class);
        $service->setRedirectTo($processRequest, 'failingRedirect');

        $originalDispatcher = Event::getFacadeRoot();
        $failingDispatcher = Mockery::mock(Dispatcher::class);
        $failingDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(RedirectToEvent::class))
            ->andThrow(new RuntimeException('Broadcast failed'));
        Event::swap($failingDispatcher);

        try {
            try {
                $service->sendRedirectToEvent();
                $this->fail('The event dispatcher should have thrown an exception.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Broadcast failed', $exception->getMessage());
            }

            // A retry without a new redirect must not dispatch the failed event again.
            $service->sendRedirectToEvent();
        } finally {
            Event::swap($originalDispatcher);
        }
    }
}
