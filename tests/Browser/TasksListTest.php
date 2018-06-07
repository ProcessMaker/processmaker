<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class TasksListTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     *  Render page task list.
     *
     * @throws \Throwable
     */
    public function testPageTask()
    {
        $this->browse(function (Browser $browser) {
            $this->artisan('db:seed');

            $user = User::find(1);
            $process = factory(Process::class)->create([
                'creator_user_id' => $user->id
            ]);

            factory(Task::class, 10)->create([
                'process_id' => $process->id
            ]);

            $browser->loginAs($user)
                ->visit('/process/' . $process->uid . '/tasks')
                ->assertSee('Tasks');
        });
    }
}
