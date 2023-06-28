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
        $myObject->name = 'John';
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

    public function testGetNamesByIds()
    {
        //Case 1: Existing Model, Existing Name, More than one ID
        $stringModel = 'ProcessCategory';
        $stringIds = '1,2';
        $columnName = 'name';
        $this->assertEquals(
            "Default Templates, System",
            ArrayHelper::getNamesByIds($stringModel, $stringIds, $columnName)
        );

        //Case 2: Existing Model, Existing Name, one ID
        $stringModel = 'ProcessCategory';
        $stringIds = '3';
        $columnName = 'name';
        $this->assertEquals(
            "Uncategorized",
            ArrayHelper::getNamesByIds($stringModel, $stringIds, $columnName)
        );

        //Case 3: Not Existing Model, Existing Name, More than one ID
        $stringModel = 'ProcessCategoryFake';
        $stringIds = '1,2';
        $columnName = 'name';
        $this->assertEquals(
            "",
            ArrayHelper::getNamesByIds($stringModel, $stringIds, $columnName)
        );

        //Case 4: Not Existing Model, Not Existing Name, More than one ID
        $stringModel = 'ProcessCategoryFake';
        $stringIds = '1,3';
        $columnName = 'nameFake';
        $this->assertEquals(
            "",
            ArrayHelper::getNamesByIds($stringModel, $stringIds, $columnName)
        );

        //Case 5: Not Existing Model, Not Existing Name, Not existing ID
        $stringModel = 'ProcessCategoryFake';
        $stringIds = '';
        $columnName = 'nameFake';
        $this->assertEquals(
            "",
            ArrayHelper::getNamesByIds($stringModel, $stringIds, $columnName)
        );

        //Case 6: Not Existing Package
        $stringModel = 'ProcessCategoryFake';
        $stringIds = '';
        $columnName = 'nameFake';
        $packageName = 'packageFake';
        $this->assertEquals(
            "",
            ArrayHelper::getNamesByIds($stringModel, $stringIds, $columnName, $packageName)
        );
    }
}
