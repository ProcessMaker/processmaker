<?php

namespace Tests\Browser;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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
    public function testListTask()
    {
        //populate data for test
        $this->artisan('db:seed');

        $user = User::find(1);
        $process = factory(Process::class)->create([
            'user_id' => $user->id
        ]);

        // Create 5 random tasks for the user
        $tasks = factory(Task::class, 5)->create([
            'process_id' => $process->id
        ]);
        $titleSearch = 'Task title of Search...';
        factory(Task::class)->create([
            'title' => $titleSearch,
            'process_id' => $process->id
        ]);
        // Now we have six tasks in the system

        $this->browse(function (Browser $browser) use ($user, $process, $tasks, $titleSearch) {

            $tableId = '#tasks-listing';
            $tableRowsSelector = $tableId . ' tbody tr';
            $inputSearch = '#tasks-listing-search';

            // Make sure we have six tasks in the database

            $elements = $browser->loginAs($user)
                ->visit('/process/' . $process->uid->toString() . '/tasks')
                ->waitFor($tableId)
                ->assertSee('Tasks')
                // Wait until the no data available message is gone (meaning we received results)
                ->waitUntilMissing('.vuetable-empty-result')
                ->elements($tableRowsSelector);

            //Rows(Tasks introduced)
            $this->assertCount(6, $elements);

            //name of columns
            $browser->whenAvailable($tableId, function ($grid) {
                $grid->assertSee('TITLE');
                $grid->assertSee('DESCRIPTION');
                $grid->assertSee('TYPE');
                $grid->assertSee('CREATED AT');
                $grid->assertSee('UPDATED AT');
            });

            $inputField = $browser->elements($inputSearch);

            //Set input search
            $browser->type($inputSearch, $titleSearch)
                ->pause(5000);

            //validating result of search
            $elements =  $browser->waitFor($tableId)
                ->assertSee('Tasks')
                ->elements($tableRowsSelector);

            //rows in search
            $this->assertCount(1, $elements);
            $browser->assertSee($titleSearch);
            $browser->assertDontSee($tasks[0]->title);

        });
    }
}
