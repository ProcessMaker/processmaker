<?php

namespace Tests\Sdk;

use ProcessMaker\BuildSdk;
use Tests\TestCase;

class BuildSdkTest extends TestCase
{
    public function setUpSuppressOutput() : void
    {
        $this->setOutputCallback(function () {
        });
    }

    private function jsonFile()
    {
        return base_path('storage/api-docs/api-docs.json');
    }

    public function testWithUnsupportedLanguage()
    {
        $this->markTestSkipped('FOUR-6653');

        $builder = new BuildSdk($this->jsonFile(), '/tmp/output');
        try {
            $builder->setLang('foo');
            $this->fail('Exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('foo language is not supported', $e->getMessage());
        }
    }

    public function testBuildPhp()
    {
        $this->markTestSkipped('FOUR-6653');

        $output = '/tmp/output';
        $builder = new BuildSdk($this->jsonFile(), $output);
        $builder->setLang('php');
        $builder->run();

        $userApiFile = $output . '/lib/Api/UsersApi.php';
        $this->assertFileExists($userApiFile);
    }
}
