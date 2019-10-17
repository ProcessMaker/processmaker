<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessRequest;
use Tests\Browser\Pages\RequestsPage;

class RequestsSearchTest extends DuskTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $sqlFile = sys_get_temp_dir() . "/testDB.sql";
        $arg = [
            env('DB_HOSTNAME'),
            env('DB_PORT'),
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_DATABASE'),
            $sqlFile
        ];

        if (!file_exists($sqlFile) || env("CLEAN_DB", false) === true) {
            $this->artisan('migrate:fresh', [
                '--seed' => true,
                '--force' => true
            ]);
            $cmd = __DIR__ . "/mysql_backup.sh " . implode(" ", $arg);
            exec($cmd, $out, $ret);
        } else {
            $cmd = __DIR__ . "/mysql_restore.sh " . implode(" ", $arg) . " 2>&1";
            exec($cmd, $out, $ret);
        }
    }

    public function testPmqlErrors()
    {
        $user = User::first();

        factory(ProcessRequest::class)->create([
            'name' => 'Some Request',
            'user_id' => $user->id,
            'status' => 'ACTIVE'
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new RequestsPage)
                ->click("button[data-original-title='Advanced Search']")
                ->waitFor('@pmql')

                ->type('@pmql', 'foo = "bar"')
                ->click('@search-button')
                ->waitFor('.alert-wrapper div')
                ->assertSeeIn('.alert-wrapper div', "Unknown column 'foo'")

                ->type('@pmql', 'name = "bar"')
                ->click('@search-button')
                ->waitForText('No Data Available')

                ->type('@pmql', 'name like "some%"') // In Progress
                ->click('@search-button')
                ->waitFor('@vuetable td')
                ->assertSeeIn("@vuetable", "In Progress")
                ;
        });
    }
}
