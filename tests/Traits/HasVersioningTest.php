<?php

namespace Tests\Traits;

use Illuminate\Support\Carbon;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

class HasVersioningTest extends TestCase
{
    public function testVersionFor()
    {
        $this->markTestSkipped('Skip version locking for now');

        $date = Carbon::now();
        $screen = factory(Screen::class)->create([
            'description' => 'first version',
        ]);

        Carbon::setTestNow($date->addDays(1));
        $screen->description = 'second version';
        $screen->save();

        Carbon::setTestNow($date->addDays(2));
        $processRequest = factory(ProcessRequest::class)->create();

        Carbon::setTestNow($date->addDays(3));
        $screen->description = 'third version';
        $screen->save();

        Carbon::setTestNow($date->addDays(4));
        factory(ProcessRequestToken::class)->create([
            'process_request_id' => $processRequest->id,
        ]);

        Carbon::setTestNow($date->addDays(5));
        $screen->description = 'fourth version';
        $screen->save();

        $screenVersion = $screen->versionFor($processRequest);
        $this->assertEquals('second version', $screenVersion->description);
    }

    public function tearDownCarbon()
    {
        Carbon::setTestNow(); // reset
    }
}
