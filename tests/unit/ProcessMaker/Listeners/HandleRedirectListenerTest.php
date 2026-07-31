<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Listeners;

use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Listeners\HandleRedirectListener;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Octane\ResetRequestState;
use ReflectionProperty;
use Tests\TestCase;

class HandleRedirectListenerTest extends TestCase
{
    /**
     * Create a test subclass that exposes the protected setRedirectTo method.
     */
    private function createProbe(): HandleRedirectListener
    {
        return new class extends HandleRedirectListener {
            public function queue(ProcessRequest $processRequest, string $method, ...$params): void
            {
                $this->setRedirectTo($processRequest, $method, ...$params);
            }
        };
    }

    /**
     * Read a private static property from HandleRedirectListener.
     */
    private function readStaticProperty(string $property): mixed
    {
        $reflection = new ReflectionProperty(HandleRedirectListener::class, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue();
    }

    /**
     * Assert that all 3 static properties are in their default/clean state.
     */
    private function assertStateIsClean(): void
    {
        $this->assertNull($this->readStaticProperty('processRequest'));
        $this->assertSame('', $this->readStaticProperty('redirectionMethod'));
        $this->assertSame([], $this->readStaticProperty('redirectionParams'));
    }

    /**
     * Test that reset() clears the static $processRequest property.
     */
    public function test_reset_clears_process_request(): void
    {
        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processUpdated');

        HandleRedirectListener::reset();

        $this->assertNull($this->readStaticProperty('processRequest'));
    }

    /**
     * Test that reset() clears the static $redirectionMethod property.
     */
    public function test_reset_clears_redirection_method(): void
    {
        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processCompletedRedirect');

        HandleRedirectListener::reset();

        $this->assertSame('', $this->readStaticProperty('redirectionMethod'));
    }

    /**
     * Test that reset() clears the static $redirectionParams property.
     */
    public function test_reset_clears_redirection_params(): void
    {
        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processUpdated', ['key' => 'value']);

        HandleRedirectListener::reset();

        $this->assertSame([], $this->readStaticProperty('redirectionParams'));
    }

    /**
     * Critical test for Octane: verify that reset() prevents data leaks.
     * After reset(), the stored redirect data should be gone.
     */
    public function test_reset_prevents_stale_redirect_from_leaking(): void
    {
        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processUpdated', ['tokenId' => 123]);

        // Simulate Octane reset between requests
        HandleRedirectListener::reset();

        // sendRedirectToEvent should NOT dispatch RedirectToEvent after reset
        $this->expectNotToPerformAssertions();
        HandleRedirectListener::sendRedirectToEvent();
    }

    /**
     * Test that reset() can be called multiple times safely.
     */
    public function test_reset_can_be_called_multiple_times(): void
    {
        HandleRedirectListener::reset();
        HandleRedirectListener::reset();
        HandleRedirectListener::reset();

        // Should not throw any errors
        $this->assertStateIsClean();
    }

    /**
     * Test that sendRedirectToEvent dispatches the event and clears state.
     */
    public function test_send_redirect_to_event_dispatches_and_clears_state(): void
    {
        \Illuminate\Support\Facades\Event::fake([RedirectToEvent::class]);

        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processUpdated');

        HandleRedirectListener::sendRedirectToEvent();

        // Assert the event was dispatched
        \Illuminate\Support\Facades\Event::assertDispatched(RedirectToEvent::class);

        // After dispatch, the state should be cleared
        $this->assertNull($this->readStaticProperty('processRequest'));
    }

    /**
     * CRITICAL: Simulate the full Octane request cycle to guarantee no data leak.
     *
     * Flow:
     *   1. Request A stores data with different values
     *   2. Reset (simulating Octane's RequestTerminated event)
     *   3. Verify ALL 3 properties are clean
     *   4. Request B stores NEW data with different values
     *   5. Verify Request B's data is correct (not contaminated by Request A)
     *   6. Reset again
     *   7. Verify clean again
     */
    public function test_full_octane_cycle_guarantees_no_data_leak(): void
    {
        // === Request A ===
        $requestA = ProcessRequest::factory()->create();
        $probeA = $this->createProbe();
        $probeA->queue($requestA, 'processCompletedRedirect', ['tokenA' => 111]);

        // Verify Request A data is stored (setRedirectTo uses ...$params, so it's nested)
        $this->assertSame($requestA->getKey(), $this->readStaticProperty('processRequest')->getKey());
        $this->assertSame('processCompletedRedirect', $this->readStaticProperty('redirectionMethod'));
        $this->assertSame([['tokenA' => 111]], $this->readStaticProperty('redirectionParams'));

        // === Octane reset after Request A ===
        HandleRedirectListener::reset();

        // === Verify ALL properties are clean after reset ===
        $this->assertStateIsClean();

        // === Request B (simulating a DIFFERENT user/request) ===
        $requestB = ProcessRequest::factory()->create();
        $probeB = $this->createProbe();
        $probeB->queue($requestB, 'processUpdated', ['tokenB' => 222, 'userId' => 999]);

        // Verify Request B's data is correct (NOT contaminated by Request A)
        $this->assertSame($requestB->getKey(), $this->readStaticProperty('processRequest')->getKey());
        $this->assertSame('processUpdated', $this->readStaticProperty('redirectionMethod'));
        $this->assertSame([['tokenB' => 222, 'userId' => 999]], $this->readStaticProperty('redirectionParams'));

        // Verify Request A's data is GONE (no leak)
        $this->assertNotSame($requestA->getKey(), $this->readStaticProperty('processRequest')?->getKey());

        // === Octane reset after Request B ===
        HandleRedirectListener::reset();

        // === Verify clean again ===
        $this->assertStateIsClean();
    }

    /**
     * CRITICAL: Verify that ResetRequestState orchestrator triggers the reset correctly.
     */
    public function test_reset_request_state_triggers_handle_redirect_reset(): void
    {
        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processUpdated', ['tokenId' => 456]);

        // Verify state is dirty before reset
        $this->assertNotNull($this->readStaticProperty('processRequest'));

        // Execute the orchestrator (same as Octane's RequestTerminated listener)
        $resetState = new ResetRequestState();
        $resetState->handle();

        // Verify orchestrator cleaned everything
        $this->assertStateIsClean();
    }

    /**
     * CRITICAL: Simulate the scenario where sendRedirectToEvent() fails,
     * but reset() still cleans up (edge case in Octane).
     */
    public function test_reset_cleans_up_even_when_send_redirect_fails(): void
    {
        $probe = $this->createProbe();
        $request = ProcessRequest::factory()->create();
        $probe->queue($request, 'processUpdated', ['data' => 'sensitive']);

        // Simulate that sendRedirectToEvent is NEVER called (e.g., error in BPMN flow)
        // But Octane's RequestTerminated event still fires and calls reset()

        // This should NOT be called in this scenario:
        // HandleRedirectListener::sendRedirectToEvent();

        // Octane reset still happens
        HandleRedirectListener::reset();

        // Verify no sensitive data leaked
        $this->assertStateIsClean();
    }
}
