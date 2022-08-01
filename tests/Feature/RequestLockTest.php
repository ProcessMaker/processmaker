<?php

namespace Tests\Feature;

use ProcessMaker\Jobs\BpmnAction;
use ProcessMaker\Models\ProcessRequestLock;
use Tests\TestCase;

class RequestLockTest extends TestCase
{
    public function testExitJobWithoutUnlock()
    {
        $request = new TestBpmnActionLock;
        $request->requestLock([1]);
        $locks = ProcessRequestLock::get();
        $this->assertCount(1, $locks);
        $this->assertNull($locks[0]->due_at);
    }

    public function testCurrentLock()
    {
        $request = new TestBpmnActionLock;
        $request->requestLock([1]);
        $currentLock = $request->currentLock([1]);
        $this->assertNotNull($currentLock);
        $this->assertNull($currentLock->due_at);
    }

    public function testRequestLockInParallel()
    {
        $request = new TestBpmnActionLock;
        $lock = $request->requestLock([1]);
        $request->requestLock([1]);
        $currentLock = $request->currentLock([1]);
        $this->assertNotNull($currentLock);
        $this->assertNull($currentLock->due_at);
        $this->assertEquals($lock->id, $currentLock->id);
    }

    public function testUnlock()
    {
        $request = new TestBpmnActionLock;
        $request->requestLock([1]);
        $request->requestLock([1]);
        $request->unlock();
        $locksInQueue = ProcessRequestLock::get();
        $locksActive = ProcessRequestLock::whereNotNull('due_at')->get();
        $this->assertCount(2, $locksInQueue);
        $this->assertCount(0, $locksActive);
    }

    public function testActivateLock()
    {
        $request = new TestBpmnActionLock;
        $lock = $request->requestLock([1]);
        $request->requestLock([1]);
        $request->unlock();
        $request->activateLock($lock);
        $locksInQueue = ProcessRequestLock::get();
        $locksActive = ProcessRequestLock::whereNotNull('due_at')->get();
        $this->assertCount(2, $locksInQueue);
        $this->assertCount(1, $locksActive);
    }

    public function testActivateLocksThenUnlock()
    {
        $request = new TestBpmnActionLock;
        $lock1 = $request->requestLock([1]);
        $lock2 = $request->requestLock([1]);
        $request->unlock();
        $request->activateLock($lock1);
        $request->unlock();
        $locksInQueue = ProcessRequestLock::get();
        $locksActive = ProcessRequestLock::whereNotNull('due_at')->get();
        $this->assertCount(1, $locksInQueue);
        $this->assertCount(0, $locksActive);

        $request->activateLock($lock2);
        $request->unlock();
        $locksInQueue = ProcessRequestLock::get();
        $locksActive = ProcessRequestLock::whereNotNull('due_at')->get();
        $this->assertCount(0, $locksInQueue);
        $this->assertCount(0, $locksActive);
    }
}

class TestBpmnActionLock extends BpmnAction
{
    protected $instanceId = 1;

    public function requestLock($ids)
    {
        return parent::requestLock($ids);
    }

    public function currentLock($ids)
    {
        return parent::currentLock($ids);
    }

    public function activateLock(ProcessRequestLock $lock)
    {
        return parent::activateLock($lock);
    }

    public function unlock()
    {
        return parent::unlock();
    }
}
