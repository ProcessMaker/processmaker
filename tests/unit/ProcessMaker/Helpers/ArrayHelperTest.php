<?php

namespace Tests\unit\ProcessMaker\Helpers;

use ProcessMaker\Helpers\ArrayHelper;
use stdClass;
use Tests\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testStdClassToArray()
    {
        $myObject = new stdClass();
        $myObject->name = "John";
        $myObject->id = 5;
        $this->assertEquals(
            [
                'name' => 'John',
                'id' => 5
            ],
            ArrayHelper::stdClassToArray($myObject)
        );
    }

    public function testGetArrayDifferencesWithFormat()
    {
        //Case 1: Two arrays with same keys and different values
        $this->assertEquals([
            '+ name' => 'Maria',
            '- name' => 'Laura',
            '+ email' => 'maria@mail.com',
            '- email' => 'laura@mail.com'
        ], ArrayHelper::getArrayDifferencesWithFormat(
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com'
            ],
            [
                'name' => 'Laura',
                'email' => 'laura@mail.com'
            ]
        ));

        //Case 2: Two arrays with same keys and only one value different
        $this->assertEquals([
            '+ email' => 'maria@mail.com',
            '- email' => 'laura@mail.com'
        ], ArrayHelper::getArrayDifferencesWithFormat(
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com'
            ],
            [
                'name' => 'Maria',
                'email' => 'laura@mail.com'
            ]
        ));

        //Case 3: One array has more keys than second array
        $this->assertEquals([
            '+ phone' => '123456'
        ], ArrayHelper::getArrayDifferencesWithFormat(
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com',
                'phone' => '123456'
            ],
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com'
            ]
        ));
    }
}
