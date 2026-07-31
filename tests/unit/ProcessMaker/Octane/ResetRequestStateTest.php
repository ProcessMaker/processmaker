<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Octane;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestTerminated;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Octane\ResetRequestState;
use ProcessMaker\Providers\ProcessMakerServiceProvider;
use ProcessMaker\Services\RedirectToEventService;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ResetRequestStateTest extends TestCase
{
    public function test_it_clears_request_timing_before_the_next_request(): void
    {
        DB::select('SELECT 1');

        $this->assertGreaterThan(0, ProcessMakerServiceProvider::getQueryTime());

        $listener = app(ResetRequestState::class);
        $listener->handle();

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());
    }

    public function test_it_prevents_redirect_state_from_leaking_into_the_next_request(): void
    {
        Event::fake([RedirectToEvent::class]);

        $redirectListener = new RedirectStateProbe();
        $redirectListener->queue(ProcessRequest::factory()->create());

        $listener = app(ResetRequestState::class);
        $listener->handle();

        app(RedirectToEventService::class)->sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_octane_request_termination_automatically_resets_request_state(): void
    {
        Event::fake([RedirectToEvent::class]);

        $redirectListener = new RedirectStateProbe();
        $redirectListener->queue(ProcessRequest::factory()->create());

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

final class RedirectStateProbe extends HandleRedirectListener
{
    public function queue(ProcessRequest $processRequest): void
    {
        $this->setRedirectTo($processRequest, 'processUpdated');
    }
}
