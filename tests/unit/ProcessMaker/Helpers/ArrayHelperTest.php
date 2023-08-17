<?php

namespace ProcessMaker\Helpers;

use ProcessMaker\Helpers\ArrayHelper;
use stdClass;
use Tests\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testStdClassToArray()
    {
        $myObject = new stdClass();
        $myObject->name = 'John';
        $myObject->id = 5;
        $this->assertEquals(
            [
                'name' => 'John',
                'id' => 5,
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
            '- email' => 'laura@mail.com',
        ], ArrayHelper::getArrayDifferencesWithFormat(
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com',
            ],
            [
                'name' => 'Laura',
                'email' => 'laura@mail.com',
            ]
        ));

        //Case 2: Two arrays with same keys and only one value different
        $this->assertEquals([
            '+ email' => 'maria@mail.com',
            '- email' => 'laura@mail.com',
        ], ArrayHelper::getArrayDifferencesWithFormat(
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com',
            ],
            [
                'name' => 'Maria',
                'email' => 'laura@mail.com',
            ]
        ));

        //Case 3: One array has more keys than second array
        $this->assertEquals([
            '+ phone' => '123456',
        ], ArrayHelper::getArrayDifferencesWithFormat(
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com',
                'phone' => '123456',
            ],
            [
                'name' => 'Maria',
                'email' => 'maria@mail.com',
            ]
        ));
    }

    public function testReplaceKeyInArray()
    {
        //Case 1: Existing Key
        $myArray = [
            'name' => 'John',
            'title' => 'Musician',
        ];
        $oldKey = 'title';
        $newKey = 'job_title';
        $this->assertEquals(
            [
                'name' => 'John',
                'job_title' => 'Musician',
            ],
            ArrayHelper::replaceKeyInArray($myArray, $oldKey, $newKey)
        );

        //Case 2: Not Existing Key
        $myArray = [
            'name' => 'John',
            'title' => 'Musician',
        ];
        $oldKey = 'titles';
        $newKey = 'job_title';
        $this->assertEquals(
            [
                'name' => 'John',
                'title' => 'Musician',
            ],
            ArrayHelper::replaceKeyInArray($myArray, $oldKey, $newKey)
        );
    }
    
    public function testCustomArrayDiffAssoc()
    {
        $array1 = [
            "a" => "green",
            "b" => "brown",
            "c" => "blue",
            "red"
        ];
        $array2 = [
            "a" => "green",
            "yellow",
            1 => "red"
        ];
        $expected = [
            "b" => "brown",
            "c" => "blue",
            0 => "red"
        ];
        $actual = ArrayHelper::customArrayDiffAssoc($array1, $array2);
        $this->assertEquals($expected, $actual);

        $array1 = [
            "a" => "green",
            "b" => "brown",
            "c" => "blue",
            "red",
            "d" => []
        ];
        $array2 = [
            "a" => "green",
            "yellow",
            1 => "red"
        ];
        $expected = [
            "b" => "brown",
            "c" => "blue",
            0 => "red",
            "d" => []
        ];
        $actual = ArrayHelper::customArrayDiffAssoc($array1, $array2);
        $this->assertEquals($expected, $actual);

        $array1 = [
            "a" => "green",
            "b" => "brown",
            "c" => "blue",
            "red",
            "d" => []
        ];
        $array2 = [
            "a" => "green",
            "yellow",
            1 => "red",
            "d" => []
        ];
        $expected = [
            "b" => "brown",
            "c" => "blue",
            0 => "red"
        ];
        $actual = ArrayHelper::customArrayDiffAssoc($array1, $array2);
        $this->assertEquals($expected, $actual);
    }
}
