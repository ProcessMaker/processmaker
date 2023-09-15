<?php

namespace Tests\unit\ProcessMaker\Helpers;

use ProcessMaker\Helpers\SensitiveDataHelper;
use Tests\TestCase;

class SensitiveDataHelperTest extends TestCase
{
    public function testParseString()
    {
        $this->assertEquals('****', SensitiveDataHelper::parseString('1234'));
    }

    public function testIsSensitiveKey()
    {
        $this->assertTrue(SensitiveDataHelper::isSensitiveKey('password'));
        $this->assertTrue(SensitiveDataHelper::isSensitiveKey('PASSWORD'));
        $this->assertTrue(SensitiveDataHelper::isSensitiveKey('- PASSWORD'));
        $this->assertTrue(SensitiveDataHelper::isSensitiveKey('+ PASSWORD'));
        $this->assertFalse(SensitiveDataHelper::isSensitiveKey('name'));
    }

    public function testParseArray()
    {
        $this->assertEquals([
            'password' => '****',
            'name' => 'User Name',
        ], SensitiveDataHelper::parseArray([
            'password' => '1234',
            'name' => 'User Name',
        ]));

        $this->assertEquals([
            'user' => [
                'name' => 'User Name',
                'password' => '****',
            ],
        ], SensitiveDataHelper::parseArray([
            'user' => [
                'name' => 'User Name',
                'password' => '1234',
            ],
        ]));

        $this->assertEquals([
            'process_name' => 'Process Name',
            'task' => null,
            'user' => [
                'name' => 'User Name',
                'email' => 'user@example.com',
            ],
        ], SensitiveDataHelper::parseArray([
            'process_name' => 'Process Name',
            'task' => null,
            'user' => [
                'name' => 'User Name',
                'email' => 'user@example.com',
            ],
        ]));
    }
}
