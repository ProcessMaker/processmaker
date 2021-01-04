<?php
namespace Tests;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Screen;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\ScreenConsolidator;

class ScreenConsolidatorTest extends TestCase
{
    public function test()
    {
        $this->be(factory(User::class)->create());

        $content = file_get_contents(
            __DIR__ . '/Fixtures/nested_screen_process.json'
        );
        ImportProcess::dispatchNow($content);

        $screen = Screen::where('title', 'parent')->firstOrFail();

        $consolidator = new ScreenConsolidator($screen);
        $result = $consolidator->call();
 
        $this->assertCount(2, $result['config']);
        $this->assertCount(6, $result['config'][0]['items']);

        $parent = $result['config'][0]['items'];
        
        $this->assertEquals('FormMultiColumn', $parent[1]['component']);
        $multiColumn = $parent[1]['items'];
        $this->assertEquals('FormInput', $multiColumn[0][0]['component']);
        $this->assertEquals('<p>Child</p>', $multiColumn[1][0]['config']['content']);

        $this->assertEquals('FormHtmlViewer', $parent[2]['component']);
        $this->assertEquals('<p>Child 2</p>', $parent[2]['config']['content']);

        $this->assertEquals('FormMultiColumn', $parent[3]['component']);
        $this->assertCount(0, $parent[3]['items'][0]);
        $this->assertCount(0, $parent[3]['items'][1]);

        $this->assertEquals('FormHtmlViewer', $parent[4]['component']);
        $this->assertEquals('<p>Child 3</p>', $parent[4]['config']['content']);

        $this->assertEquals('parent watcher test', $result['watchers'][0]['name']);
        $this->assertEquals('child watcher', $result['watchers'][1]['name']);

        $this->assertEquals(1, $result['computed'][0]['id']);
        $this->assertEquals(2, $result['computed'][1]['id']);
        
        $this->assertEquals("* { color: blue }\n* { color: red }", $result['custom_css']);
    }
}