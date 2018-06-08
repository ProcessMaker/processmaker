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
            'creator_user_id' => $user->id
        ]);

        $items = Faker::create()->numberBetween(1,9);
        $tasks = factory(Task::class, $items)->create([
            'process_id' => $process->id
        ]);
        $titleSearch = 'Task title of Search...';
        factory(Task::class)->create([
            'title' => $titleSearch,
            'process_id' => $process->id
        ]);
        $items++;

        $this->browse(function (Browser $browser) use ($user, $process, $tasks, $items, $titleSearch) {

            $tableId = '#tasks-listing';
            $tableRowsSelector = $tableId . ' tbody tr';
            $inputSearch = '#tasks-listing-search';

            $elements = $browser->loginAs($user)
                ->visit('/process/' . $process->uid . '/tasks')
                ->waitFor($tableId)
                ->assertSee('Tasks')
                ->elements($tableRowsSelector);

            //Rows(Tasks introduced)
            $this->assertCount($items, $elements);

            //name of columns
            $browser->whenAvailable($tableId, function ($grid) {
                $grid->assertSee('TITLE');
                $grid->assertSee('DESCRIPTION');
                $grid->assertSee('TYPE');
                $grid->assertSee('CREATED AT');
                $grid->assertSee('UPDATED AT');
            });

            //Set input search
            $browser->type($inputSearch, $titleSearch)
                ->pause(500);

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
