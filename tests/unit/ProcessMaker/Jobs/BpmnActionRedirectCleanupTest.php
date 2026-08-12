<?php

namespace Tests\Unit\ProcessMaker\Jobs;

use Mockery;
use ProcessMaker\Jobs\BpmnAction;
use ProcessMaker\Services\RedirectToEventService;
use Tests\TestCase;

class BpmnActionRedirectCleanupTest extends TestCase
{
    public function test_redirect_state_is_reset_when_bpmn_context_loading_fails(): void
    {
        $redirectToEventService = Mockery::mock(RedirectToEventService::class);
        $redirectToEventService->shouldReceive('sendRedirectToEvent')->never();
        $redirectToEventService->shouldReceive('reset')->once();
        app()->instance(RedirectToEventService::class, $redirectToEventService);

        $job = new class extends BpmnAction {
            protected $definitionsId = -1;
        };

        $this->assertNull($job->handle());
    }
}
