<?php

namespace Tests\Unit\ProcessMaker\Repositories;

use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\ProcessExecutionRawRepository;
use Tests\TestCase;

class ProcessExecutionRawRepositoryTest extends TestCase
{
    public function testTaskHasDraftRawReturnsFalseWhenNoDraftExists(): void
    {
        $task = ProcessRequestToken::factory()->create();

        $repository = new ProcessExecutionRawRepository();

        $this->assertFalse($repository->taskHasDraftRaw($task->id));
    }

    public function testHydrateModelFromRowRawPreservesAttributes(): void
    {
        $user = User::factory()->create();

        $repository = new ProcessExecutionRawRepository();
        $hydrated = $repository->hydrateModelFromRowRaw(User::class, (object) $user->getAttributes());

        $this->assertSame($user->id, $hydrated->id);
        $this->assertTrue($hydrated->exists);
    }
}
