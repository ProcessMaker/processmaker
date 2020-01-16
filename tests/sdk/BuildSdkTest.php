<?php
namespace Tests\Model;

use Tests\TestCase;
use ProcessMaker\BuildSdk;

class BuildSdkTest extends TestCase
{
    private function jsonFile() {
        return base_path('storage/api-docs/api-docs.json');
    }

    public function testWithUnsupportedLanguage()
    {   
        $builder = new BuildSdk($this->jsonFile(), '/tmp/output');
        try {
            $builder->setLang('foo');
            $this->fail('Exception was not thrown.');
        } catch(\Exception $e) {
            $this->assertContains("foo language is not supported", $e->getMessage());
        }
    }
    
    public function testBuildPhp()
    {
        $output = '/tmp/output';
        $builder = new BuildSdk($this->jsonFile(), $output);
        $builder->setLang('php');
        $builder->run();
        
        $userApiFile = $output . "/lib/Api/UsersApi.php";
        $this->assertFileExists($userApiFile);
    }
}