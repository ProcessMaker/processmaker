<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use ProcessMaker\Jobs\BpmnContextReuseGuard;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;
use Tests\TestCase;

#[TestGroup('process_tests')]
class BpmnContextReuseGuardTest extends TestCase
{
    public function testMigrationAddsExecutionRevision(): void
    {
        $this->assertTrue(Schema::hasColumn('process_requests', 'execution_revision'));
    }

    public function testOneActiveTokenAndMatchingRevisionUsesFastPath(): void
    {
        [$loaded, $persisted] = $this->linearInstances(7);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 7, [100]);

        $this->assertNull($reason);
    }

    public function testChangedRevisionUsesFallbackWithoutReadingJson(): void
    {
        [$loaded, $persisted] = $this->linearInstances(8);
        $loaded->shouldNotReceive('getAttribute')->with('data');
        $persisted->shouldNotReceive('getAttribute')->with('data');
        $loaded->shouldNotReceive('getRawOriginal')->with('data');
        $persisted->shouldNotReceive('getRawOriginal')->with('data');

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 7, []);

        $this->assertSame('execution_revision_changed', $reason);
    }

    public function testMissingLoadedContextUsesFallback(): void
    {
        $reason = app(BpmnContextReuseGuard::class)->fallbackReason(
            null,
            $this->persistedInstance(1),
            null,
            []
        );

        $this->assertSame('context_not_loaded', $reason);
    }

    public function testMultipleActiveTokensDisableFastPath(): void
    {
        $loaded = $this->instanceWithTokens([
            $this->token('ACTIVE', false, 100),
            $this->token('ACTIVE', false, 101),
        ]);
        $persisted = $this->persistedInstance(1);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 1, []);

        $this->assertSame('execution_not_linear', $reason);
    }

    public function testClosedTokensDoNotDisableLinearFastPath(): void
    {
        $loaded = $this->instanceWithTokens([
            $this->token('ACTIVE', false, 100),
            $this->token('CLOSED', false, 101),
        ]);
        $persisted = $this->persistedInstance(3);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 3, [100]);

        $this->assertNull($reason);
    }

    public function testCompletedAndTriggeredTokensDoNotDisableLinearFastPath(): void
    {
        $loaded = $this->instanceWithTokens([
            $this->token('ACTIVE', false, 100),
            $this->token('COMPLETED', false, 101),
            $this->token('TRIGGERED', false, 102),
        ]);
        $persisted = $this->persistedInstance(3);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 3, [100]);

        $this->assertNull($reason);
    }

    public function testIncomingTokenDisablesLinearFastPath(): void
    {
        $loaded = $this->instanceWithTokens([
            $this->token('ACTIVE', false, 100),
            $this->token('INCOMING', false, 101),
        ]);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason(
            $loaded,
            $this->persistedInstance(3),
            3,
            [100, 101]
        );

        $this->assertSame('execution_not_linear', $reason);
    }

    public function testMultiInstanceTokenDisablesFastPath(): void
    {
        $loaded = $this->instanceWithTokens([$this->token('ACTIVE', true)]);
        $persisted = $this->persistedInstance(1);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 1, []);

        $this->assertSame('execution_not_linear', $reason);
    }

    public function testCollaborationDisablesFastPath(): void
    {
        $loaded = Mockery::mock(ProcessRequest::class)->makePartial();
        $loaded->shouldReceive('getRawOriginal')
            ->with('process_collaboration_id')
            ->once()
            ->andReturn(10);
        $loaded->shouldNotReceive('getTokens');

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason(
            $loaded,
            $this->persistedInstance(1),
            1,
            []
        );

        $this->assertSame('execution_not_linear', $reason);
    }

    public function testMultiplePersistedTokensDisableFastPath(): void
    {
        [$loaded, $persisted] = $this->linearInstances(2);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason(
            $loaded,
            $persisted,
            2,
            [100, 101]
        );

        $this->assertSame('persisted_execution_not_linear', $reason);
    }

    public function testPersistedTokenReplacementDisablesFastPath(): void
    {
        [$loaded, $persisted] = $this->linearInstances(2);

        $reason = app(BpmnContextReuseGuard::class)->fallbackReason($loaded, $persisted, 2, [200]);

        $this->assertSame('active_token_changed', $reason);
    }

    public function testExecutionRepositoryIncrementsRevisionAtomically(): void
    {
        $request = ProcessRequest::factory()->create(['execution_revision' => 4]);

        app(ExecutionInstanceRepository::class)->incrementExecutionRevision($request);

        $this->assertSame(5, $request->refresh()->execution_revision);
    }

    public function testTokenRepositoryStoreIncrementsRequestRevision(): void
    {
        $request = ProcessRequest::factory()->create(['execution_revision' => 9]);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
        ]);
        $token->setInstance($request);

        app(TokenRepository::class)->store($token);

        $this->assertSame(10, $request->refresh()->execution_revision);
    }

    private function linearInstances(int $persistedRevision): array
    {
        return [
            $this->instanceWithTokens([$this->token('ACTIVE', false, 100)]),
            $this->persistedInstance($persistedRevision),
        ];
    }

    private function instanceWithTokens(array $tokens): ProcessRequest
    {
        $instance = Mockery::mock(ProcessRequest::class)->makePartial();
        $instance->shouldReceive('getRawOriginal')
            ->with('process_collaboration_id')
            ->andReturn(null);
        $instance->shouldReceive('getTokens')->andReturn($tokens);

        return $instance;
    }

    private function persistedInstance(int $revision): ProcessRequest
    {
        $instance = Mockery::mock(ProcessRequest::class)->makePartial();
        $instance->execution_revision = $revision;

        return $instance;
    }

    private function token(string $status, bool $multiInstance = false, int $id = 100): ProcessRequestToken
    {
        $token = Mockery::mock(ProcessRequestToken::class)->makePartial();
        $token->shouldReceive('getStatus')->andReturn($status);
        $token->shouldReceive('isMultiInstance')->andReturn($multiInstance);
        $token->shouldReceive('getId')->andReturn($id);

        return $token;
    }
}
