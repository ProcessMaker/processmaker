<?php

namespace Tests;

use Illuminate\Support\Arr;
use DomDocument;
use DomXpath;

class PackageTestHelper {

    public function addPackageTestsToPhpuntXml($xmlFile, $composerFile, $directoryExistsFn)
    {
        $composer = json_decode(file_get_contents($composerFile), true);


        $list = array_keys(Arr::get($composer, 'extra.processmaker.enterprise', []));

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($xmlFile);

        $xpath = new DOMXpath($dom);
        $directories = $xpath->query('//testsuite[@name="Features"]')[0];
        foreach ($list as $package)
        {
            $testsDirectory = "vendor/processmaker/$package/tests";
            if ($directoryExistsFn($testsDirectory)) {
                $directory = $dom->createElement('directory', $testsDirectory);
                $directories->appendChild($directory);
            }
        }

        return $dom->save($xmlFile);
    }
}