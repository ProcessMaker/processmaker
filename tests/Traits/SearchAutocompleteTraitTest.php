<?php

namespace Tests\Traits;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SearchAutocompleteTraitTest extends TestCase
{
    use RequestHelper;

    public function testDoesNotIncludeSystemProcesses()
    {
        $regularProcessCategory = factory(ProcessCategory::class)->create();
        $systemProcessCategory = factory(ProcessCategory::class)->create(['is_system' => true]);
        $regularProcess = factory(Process::class)->create([
            'name' => 'some regular process',
            'process_category_id' => $regularProcessCategory,
        ]);
        $anotherRegularProcess = factory(Process::class)->create([
            'name' => 'another regular process',
            'process_category_id' => $regularProcessCategory,
        ]);
        $systemProcess = factory(Process::class)->create([
            'name' => 'some system process',
            'process_category_id' => $systemProcessCategory,
        ]);

        $call = function ($query) {
            $result = $this->webCall('GET', '/requests/search?'.$query);
            $result = json_decode($result->getContent(), true);
            $processes = collect($result['process']);

            return $processes->pluck('id');
        };

        $processIds = $call('type=all');
        $this->assertCount(2, $processIds);
        $this->assertContains($regularProcess->id, $processIds);
        $this->assertContains($anotherRegularProcess->id, $processIds);

        $processIds = $call('type=all&filter=some');
        $this->assertCount(1, $processIds);
        $this->assertEquals($processIds[0], $regularProcess->id);
    }
}
