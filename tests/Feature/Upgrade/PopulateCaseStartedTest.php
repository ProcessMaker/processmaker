<?php

namespace Tests\Upgrades;

use Illuminate\Support\Facades\DB;
use Mockery;
use PopulateCaseStarted;
use ProcessMaker\Models\ProcessRequest;
use Tests\TestCase;
// use Illuminate\Foundation\Testing\RefreshDatabase;

class PopulateCaseStartedTest extends TestCase
{
    // use RefreshDatabase;

    protected function setUp(): void
    {
        require base_path('upgrades/2024_10_09_032151_populate_case_started.php');
        parent::setUp();
        // Set up any necessary data or configurations here
    }

    public function testUp()
    {
        // Create an instance of the PopulateCaseStarted class
        $populateCaseStarted = new PopulateCaseStarted();

        // Create some requests, tokens, sub processes
        ProcessRequest::factory()->count(5)->create();

        // Call the up method
        dump(DB::table('cases_started')->count());
        $populateCaseStarted->up();
        dump(DB::table('cases_started')->count());

        // Assertions can be added here if needed
    }
}
