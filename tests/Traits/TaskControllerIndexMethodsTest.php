<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Process;
use ProcessMaker\Traits\TaskControllerIndexMethods;

class TaskControllerIndexMethodsTest extends TestCase
{
    use TaskControllerIndexMethods;

    /**
     * @testdox Test that isManagerOfProcess returns true for a manager
     */
    public function testIsManagerOfProcessReturnsTrueForManager()
    {
        $user = User::factory()->create();

        Process::factory()->create([
            'properties' => ['manager_id' => $user->id],
            'status' => 'ACTIVE'
        ]);

        $result = $this->isManagerOfProcess($user);

        $this->assertTrue($result);
    }

    /**
     * @testdox Test that isManagerOfProcess returns false for a non-manager
     */
    public function testIsManagerOfProcessReturnsFalseForNonManager()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Process::factory()->create([
            'properties' => ['manager_id' => $user1->id],
            'status' => 'ACTIVE'
        ]);

        $result = $this->isManagerOfProcess($user2);

        $this->assertFalse($result);
    }

    /**
     * @testdox Test that isManagerOfProcess returns false for an inactive process
     */
    public function testIsManagerOfProcessReturnsFalseForInactiveProcess()
    {
        $user = User::factory()->create();

        Process::factory()->create([
            'properties' => ['manager_id' => $user->id],
            'status' => 'INACTIVE'
        ]);

        $result = $this->isManagerOfProcess($user);

        $this->assertFalse($result);
    }
}
