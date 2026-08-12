<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Listeners;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestTerminated;
use Mockery;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Octane\ResetRequestState;
use ProcessMaker\Services\RedirectToEventService;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class HandleRedirectListenerTest extends TestCase
{
    /**
     * Create a test listener that exposes the protected setRedirectTo method.
     */
    private function createProbe(?RedirectToEventService $service = null): HandleRedirectListener
    {
        $service ??= app(RedirectToEventService::class);

        return new class ($service) extends HandleRedirectListener {
            public function queue(ProcessRequest $processRequest, string $method, ...$params): void
            {
                $this->setRedirectTo($processRequest, $method, ...$params);
            }
        };
    }

    public function test_reset_clears_process_request(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $probe = $this->createProbe($service);
        $staleRequest = ProcessRequest::factory()->create();
        $currentRequest = ProcessRequest::factory()->create();

        $probe->queue($staleRequest, 'staleRedirect');
        $service->reset();
        $probe->queue($currentRequest, 'currentRedirect');
        $service->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use ($currentRequest) {
            return $event->broadcastOn()[0]->name ===
                'private-ProcessMaker.Models.ProcessRequest.' . $currentRequest->id;
        });
        Event::assertDispatched(RedirectToEvent::class, 1);
    }

    public function test_reset_clears_redirection_method(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $probe = $this->createProbe($service);
        $request = ProcessRequest::factory()->create();

        $probe->queue($request, 'staleRedirect');
        $service->reset();
        $probe->queue($request, 'currentRedirect');
        $service->sendRedirectToEvent();

        Event::assertDispatched(
            RedirectToEvent::class,
            fn (RedirectToEvent $event) => $event->method === 'currentRedirect'
        );
    }

    public function test_reset_clears_redirection_params(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $probe = $this->createProbe($service);
        $request = ProcessRequest::factory()->create();

        $probe->queue($request, 'processUpdated', ['secret' => 'stale']);
        $service->reset();
        $probe->queue($request, 'processUpdated', ['tokenId' => 222]);
        $service->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) {
            return $event->params[0] === ['tokenId' => 222]
                && !array_key_exists('secret', $event->params[0]);
        });
    }

    public function test_reset_prevents_stale_redirect_from_leaking(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $this->createProbe($service)->queue(
            ProcessRequest::factory()->create(),
            'processUpdated',
            ['tokenId' => 123]
        );

        $service->reset();
        $service->sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_reset_can_be_called_multiple_times(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $this->createProbe($service)->queue(ProcessRequest::factory()->create(), 'processUpdated');

        $service->reset();
        $service->reset();
        $service->reset();
        $service->sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_send_redirect_to_event_dispatches_and_clears_state(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $this->createProbe($service)->queue(ProcessRequest::factory()->create(), 'processUpdated');

        $service->sendRedirectToEvent();
        $service->sendRedirectToEvent();

        Event::assertDispatched(
            RedirectToEvent::class,
            fn (RedirectToEvent $event) => $event->method === 'processUpdated'
        );
        Event::assertDispatched(RedirectToEvent::class, 1);
    }

    public function test_full_octane_cycle_guarantees_no_data_leak(): void
    {
        Event::fake([RedirectToEvent::class]);

        $requestA = ProcessRequest::factory()->create();
        $scopeA = app(RedirectToEventService::class);
        $this->createProbe($scopeA)->queue(
            $requestA,
            'processCompletedRedirect',
            ['tokenA' => 111]
        );

        app(ResetRequestState::class)->handle();
        $scopeA->sendRedirectToEvent();
        Event::assertNotDispatched(RedirectToEvent::class);

        app()->forgetScopedInstances();

        $scopeB = app(RedirectToEventService::class);
        $this->assertNotSame($scopeA, $scopeB);

        $requestB = ProcessRequest::factory()->create();
        $this->createProbe($scopeB)->queue(
            $requestB,
            'processUpdated',
            ['tokenB' => 222, 'userId' => 999]
        );
        $scopeB->sendRedirectToEvent();

        Event::assertDispatched(RedirectToEvent::class, function (RedirectToEvent $event) use ($requestB) {
            return $event->method === 'processUpdated'
                && $event->params[0] === ['tokenB' => 222, 'userId' => 999]
                && $event->broadcastOn()[0]->name ===
                    'private-ProcessMaker.Models.ProcessRequest.' . $requestB->id;
        });
        Event::assertDispatched(RedirectToEvent::class, 1);
    }

    public function test_reset_request_state_triggers_redirect_service_reset(): void
    {
        $service = Mockery::mock(RedirectToEventService::class);
        $service->shouldReceive('reset')->once();

        (new ResetRequestState($service))->handle();
    }

    public function test_octane_termination_cleans_up_when_redirect_is_never_sent(): void
    {
        Event::fake([RedirectToEvent::class]);

        $service = app(RedirectToEventService::class);
        $this->createProbe($service)->queue(
            ProcessRequest::factory()->create(),
            'processUpdated',
            ['data' => 'sensitive']
        );

        event(new RequestTerminated(
            $this->app,
            $this->app,
            Request::create('/first-request'),
            new Response()
        ));

        app(RedirectToEventService::class)->sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }
}
