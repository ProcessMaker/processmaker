<?php

namespace Tests\Feature;

use ProcessMaker\Models\ProcessRequest;
use Tests\TestCase;

class RequestLockTest extends TestCase
{
    public function testExitJobWithoutUnlock()
    {
        $request = factory(ProcessRequest::class)->create();
        $tokenId = null;
        $request->requestLock($tokenId);
        $locks = $request->locks()->get();
        $this->assertCount(1, $locks);
        $this->assertNull($locks[0]->due_at);
    }

    public function testCurrentLock()
    {
        $request = factory(ProcessRequest::class)->create();
        $tokenId = null;
        $request->requestLock($tokenId);
        $currentLock = $request->currentLock();
        $this->assertNotNull($currentLock);
        $this->assertNull($currentLock->due_at);
    }

    public function testRequestLockInParallel()
    {
        $request = factory(ProcessRequest::class)->create();
        $tokenId = null;
        $lock = $request->requestLock($tokenId);
        $request->requestLock($tokenId);
        $currentLock = $request->currentLock();
        $this->assertNotNull($currentLock);
        $this->assertNull($currentLock->due_at);
        $this->assertEquals($lock->id, $currentLock->id);
    }

    public function testUnlock()
    {
        $request = factory(ProcessRequest::class)->create();
        $tokenId = null;
        $request->requestLock($tokenId);
        $request->requestLock($tokenId);
        $request->unlock();
        $locksInQueue = $request->locks()->get();
        $locksActive = $request->locks()->whereNotNull('due_at')->get();
        $this->assertCount(2, $locksInQueue);
        $this->assertCount(0, $locksActive);
    }

    public function testActivateLock()
    {
        $request = factory(ProcessRequest::class)->create();
        $tokenId = null;
        $lock = $request->requestLock($tokenId);
        $request->requestLock($tokenId);
        $request->unlock();
        $lock->activate();
        $locksInQueue = $request->locks()->get();
        $locksActive = $request->locks()->whereNotNull('due_at')->get();
        $this->assertCount(2, $locksInQueue);
        $this->assertCount(1, $locksActive);
    }
}
