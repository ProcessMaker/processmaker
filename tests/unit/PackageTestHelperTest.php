<?php

namespace Tests\Unit;

use Tests\TestCase;
use org\bovigo\vfs\vfsStream;
use Tests\PackageTestHelper;

class PackageTestHelperTest extends TestCase
{
    public function testAddPackageTestsToPhpuntXml()
    {
        $vfs = vfsStream::setup('storage');
        $storageDir = $vfs->url();

        $directoryExistsFn = function($dir) {
            return true;
        };

        $composer = [
            'extra' => [
                'processmaker' => [
                    'enterprise' => [
                        'Package1' => '123',
                        'Package2' => '456',
                    ]
                ]
            ]
        ];
        $composerFile = $storageDir . '/composer.json';
        file_put_contents($composerFile, json_encode($composer));

        $phpunitxml = <<<END
                      <?xml version="1.0" encoding="UTF-8"?>
                        <phpunit>
                            <testsuites>
                                <testsuite name="Features">
                                    <directory>tests/Foo</directory>
                                </testsuite>
                            </testsuites>    
                        </phpunit>
                      END;
        $xmlFile = $storageDir . '/phpunit.xml';
        file_put_contents($xmlFile, $phpunitxml);
        (new PackageTestHelper)->addPackageTestsToPhpuntXml($xmlFile, $composerFile, $directoryExistsFn);   
        
        $result = file_get_contents($xmlFile);
        // dd($result);              

        $expected = <<<END
                    <?xml version="1.0" encoding="UTF-8"?>
                    <phpunit>
                      <testsuites>
                        <testsuite name="Features">
                          <directory>tests/Foo</directory>
                          <directory>vendor/processmaker/Package1/tests</directory>
                          <directory>vendor/processmaker/Package2/tests</directory>
                        </testsuite>
                      </testsuites>
                    </phpunit>

                    END;

        $this->assertEquals($expected, $result);
    }
}
        