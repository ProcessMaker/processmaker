<?php

namespace ProcessMaker\Models;

use Tests\TestCase;

class GroupTest extends TestCase
{
    /**
     * Test group without manager.
     *
     * @return void
     */
    public function testGroupWithManager()
    {
        $manager = factory(User::class)->create();
        $group = factory(Group::class)->create(['manager_id' => $manager->id]);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertInstanceOf(User::class, $group->manager);
        $this->assertEquals($group->manager_id, $group->manager->id);
    }

    /**
     * Test group without manager.
     *
     * @return void
     */
    public function testGroupWithoutManager()
    {
        $group = \factory(Group::class)->make([
            'manager_id' => null,
        ]);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertNull($group->manager);
        $this->assertNull($group->manager_id);
    }
}
