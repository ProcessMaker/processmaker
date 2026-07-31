<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Octane;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestTerminated;
use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Octane\ResetRequestState;
use ProcessMaker\Providers\ProcessMakerServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ResetRequestStateTest extends TestCase
{
    protected function tearDown(): void
    {
        ProcessMakerServiceProvider::beginRequestTiming();

        parent::tearDown();
    }

    private function recordQueryDuration(float $milliseconds): void
    {
        $connection = DB::connection();

        event(new QueryExecuted('SELECT 1', [], $milliseconds, $connection));
    }

    public function test_it_clears_request_timing_before_the_next_request(): void
    {
        DB::select('SELECT 1');

        $this->assertGreaterThan(0, ProcessMakerServiceProvider::getQueryTime());

        $listener = new ResetRequestState();
        $listener->handle();

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());
    }

    public function test_it_prevents_redirect_state_from_leaking_into_the_next_request(): void
    {
        Event::fake([RedirectToEvent::class]);

        $redirectListener = new RedirectStateProbe();
        $redirectListener->queue(ProcessRequest::factory()->create());

        $listener = new ResetRequestState();
        $listener->handle();

        HandleRedirectListener::sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);
    }

    public function test_octane_request_termination_automatically_resets_request_state(): void
    {
        Event::fake([RedirectToEvent::class]);

        ProcessMakerServiceProvider::beginRequestTiming();
        $this->recordQueryDuration(5000);

        $firstRequestQueryTime = ProcessMakerServiceProvider::getQueryTime();

        $this->assertSame(5000.0, $firstRequestQueryTime);

        $redirectListener = new RedirectStateProbe();
        $redirectListener->queue(ProcessRequest::factory()->create());

        event(new RequestTerminated(
            $this->app,
            $this->app,
            Request::create('/first-request'),
            new Response()
        ));

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());

        HandleRedirectListener::sendRedirectToEvent();

        Event::assertNotDispatched(RedirectToEvent::class);

        $this->recordQueryDuration(10);

        $nextRequestQueryTime = ProcessMakerServiceProvider::getQueryTime();

        $this->assertSame(10.0, $nextRequestQueryTime);
    }

    public function test_octane_request_termination_resets_timing_after_an_error_response(): void
    {
        DB::select('SELECT 1');

        $this->assertGreaterThan(0, ProcessMakerServiceProvider::getQueryTime());

        event(new RequestTerminated(
            $this->app,
            $this->app,
            Request::create('/failed-request'),
            new Response(status: 500)
        ));

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());
    }
}

final class RedirectStateProbe extends HandleRedirectListener
{
    public function queue(ProcessRequest $processRequest): void
    {
        $this->setRedirectTo($processRequest, 'processUpdated');
    }
}
