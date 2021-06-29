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

    public function testActivateLocksThenUnlock()
    {
        $request = factory(ProcessRequest::class)->create();
        $tokenId = null;
        $lock1 = $request->requestLock($tokenId);
        $lock2 = $request->requestLock($tokenId);
        $request->unlock();
        $lock1->activate();
        $request->unlock();
        $locksInQueue = $request->locks()->get();
        $locksActive = $request->locks()->whereNotNull('due_at')->get();
        $this->assertCount(1, $locksInQueue);
        $this->assertCount(0, $locksActive);

        $lock2->activate();
        $request->unlock();
        $locksInQueue = $request->locks()->get();
        $locksActive = $request->locks()->whereNotNull('due_at')->get();
        $this->assertCount(0, $locksInQueue);
        $this->assertCount(0, $locksActive);
    }
}
