<?php
namespace Tests\Model;

use Tests\TestCase;
use ProcessMaker\BuildSdk;

class BuildSdkTest extends TestCase
{
    public function testWithUnsupportedLanguage()
    {   
        $builder = new BuildSdk(base_path());
        try {
            $builder->setLang('foo');
            $this->fail('Exception was not thrown.');
        } catch(\Exception $e) {
            $this->assertEquals("foo language is not supported", $e->getMessage());
        }
    }
    
    public function testWithExistingFolder()
    {   
        exec('mkdir -p ' . base_path('storage/api/lua-client'));
        $builder = new BuildSdk(base_path());
        $builder->setLang('lua');
        try {
            $builder->run();
            $this->fail('Exception was not thrown.');
        } catch(\Exception $e) {
            $this->assertContains("You must manually remove the destination", $e->getMessage());
        }
    }

    public function testBuildPhp()
    {
        $userApiFile = base_path("storage/api/php-client/lib/Api/UsersApi.php");

        exec('rm -rf ' . base_path('storage/api/php-client'));
        $this->assertFileNotExists($userApiFile);

        $builder = new BuildSdk(base_path());
        $builder->setLang('php');
        $builder->run();
        
        $this->assertFileExists($userApiFile);
    }
}