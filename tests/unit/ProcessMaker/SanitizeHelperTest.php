<?php

namespace ProcessMaker;

use Tests\TestCase;

class SanitizeHelperTest extends TestCase
{
    public function testSanitize()
    {
        $this->assertEquals('test', SanitizeHelper::strip_tags('<p>test</p>'));
        $this->assertEquals('image:;', SanitizeHelper::strip_tags('image:<img src="https://example.com/image" />;'));
        $this->assertEquals('br:;', SanitizeHelper::strip_tags('br:<br />;'));
        $this->assertEquals('br:;', SanitizeHelper::strip_tags('br:<br/>;'));
        $this->assertEquals('br:;', SanitizeHelper::strip_tags('br:<br>;'));
        // This is not a valid html tag
        $this->assertEquals('Monitor <90in', SanitizeHelper::strip_tags('Monitor <90in'));
        // ADOA example
        $equipment = <<<EQUIPMENT
        Computer                         Serial # DE1013356        
        Monitor (s)                     CNK51105LD      <AF3412-23
        Keyboard (s)                    CNK51105LD      <FF0012-23
        EQUIPMENT;
        $this->assertEquals($equipment, SanitizeHelper::strip_tags($equipment));
        // strip_tags tests
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <? cool < blah ?> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <? cool > blah ?> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <!-- cool < blah --> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <!-- cool > blah --> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <? echo \"\\\"\"?> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <? echo \'\\\'\'?> STUFF'));
        $this->assertEquals('TESTS ?!!?!?!!!?!!', SanitizeHelper::strip_tags('TESTS ?!!?!?!!!?!!'));
        // test including car returns
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <? cool 
        < blah ?> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <? cool 
        > blah ?> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <!-- cool 
        < blah --> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <!--
            cool > blah
        --> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <?
            echo \"\\\"\"
        ?> STUFF'));
        $this->assertEquals('NEAT  STUFF', SanitizeHelper::strip_tags('NEAT <?
            echo \'\\\'\'
        ?> STUFF'));
    }
}
