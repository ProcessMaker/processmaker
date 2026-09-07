<?php

namespace Tests\Unit\ProcessMaker\Repositories;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Repositories\ProcessExecutionRawRepository;
use Tests\TestCase;

class ProcessExecutionRawRepositoryTest extends TestCase
{
    public function testGetProcessRequestForCompleteRawLoadsColumnsWithoutData(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['marker' => 'should_not_load'],
        ]);

        $repository = new ProcessExecutionRawRepository();
        $hydrated = $repository->getProcessRequestForCompleteRaw($request->id);

        $this->assertSame($request->id, $hydrated->id);
        $this->assertArrayHasKey('do_not_sanitize', $hydrated->getAttributes());
        $this->assertArrayNotHasKey('data', $hydrated->getAttributes());
    }

    public function testGetProcessRequestForResponseRawIncludesDataColumn(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['marker' => 'persisted'],
        ]);

        $repository = new ProcessExecutionRawRepository();
        $hydrated = $repository->getProcessRequestForResponseRaw($request->id);

        $this->assertIsArray($hydrated->data);
        $this->assertSame('persisted', $hydrated->data['marker']);
    }
}
