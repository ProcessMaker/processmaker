<?php
namespace ProcessMaker;

use \Exception;
use \ZipArchive;
use function GuzzleHttp\json_decode;

class BuildSdk {
    private $rebuild = false;
    private $debug = false;
    private $image = "openapitools/openapi-generator-cli";
    private $tag = "v4.2.2";
    private $lang = null;
    private $outputPath = null;
    private $jsonPath = null;
    private $tmpfile = null;

    public function __construct($jsonPath, $outputPath, $debug = false, $rebuild = false) {
        $this->jsonPath = $jsonPath;
        $this->outputPath = rtrim($outputPath, "/");
        $this->debug = $debug;
        $this->rebuild = $rebuild;
    }

    public function run()
    {
        $folder = "/tmp/sdk-" . $this->lang;
        $this->runCmd("rm -rf " . $folder);

        $this->writeOptionsToTmpFile();

        $this->startContainer();
        $this->cp($this->jsonPath, "generator:/api-docs.json");
        $this->cp($this->tmpfile, "generator:/config.json");
        $this->generator("validate -i /api-docs.json");
        $this->generator("generate -g php -i /api-docs.json -c /config.json -o /sdk");
        $this->cp("generator:/sdk", $folder);
        $this->stopContainer();

        $this->commentErroneousCode($folder); // lua
        $this->addMissingDependency($folder); // java
        $this->removeDateTime($folder); // csharp
        $this->runCmd("cp -rf {$folder}/. {$this->outputDir()}");

        return "DONE. Api is at {$this->outputDir()}";
    }

    private function cp($from, $to)
    {
        $this->runCmd("docker cp " . $from . " " . $to);
    }

    private function imageWithTag()
    {
        return $this->image . ":" . $this->tag;
    }

    private function startContainer()
    {
        $this->runCmd("docker run -t -d --entrypoint '/bin/sh' --name generator " . $this->imageWithTag());
    }

    private function stopContainer()
    {
        $this->runCmd("docker kill generator || true");
        $this->runCmd("docker rm generator || true");
    }

    public function setLang($value)
    {
        $langs = $this->getAvailableLanguages();
        if (!in_array($value, $langs)) {
            throw new Exception("$value language is not supported. Must be one of these: " . implode(",", $langs));
        }
        $this->lang = $value;
    }

    public function getOptions()
    {
        if (!$this->lang) {
            throw new Exception("Language must be specified using setLang()");
        }
        return $this->runCmd('docker run ' . $this->imageWithTag() . ' config-help -g ' . $this->lang);
    }
    
    public function getAvailableLanguages()
    {
        $result = $this->runCmd('docker run ' . $this->imageWithTag() . ' list -s');
        return explode(",", $result);
    }

    private function runChecks()
    {
        if (!$this->lang) {
            throw new Exception("Language must be specified using setLang()");
        }

        if (!is_dir($this->outputPath)) {
            throw new Exception("{$this->outputPath} is not a valid directory");
        }

        if (!is_writable($this->outputPath)) {
            throw new Exception("Folder is not writeable: " . $this->outputPath);
        }

        // if (is_dir($this->outputDir())) {
        //     throw new Exception("Folder exists: {$this->outputDir()}. You must manually remove the destination folder before running this script.");
        // }

        if (!is_file($this->jsonPath) || !is_readable($this->jsonPath)) {
            throw new Exception("Json file does not exist or can not be read: " . $this->jsonPath);
        }

        // if (json_decode($this->apiJsonRaw()) === null) {
        //     throw new Exception("File is not valid json: " . $this->jsonPath);
        // }

    }

    private function outputDir()
    {
        return $this->outputPath;
    }

    private function generator($cmd)
    {
        return $this->runCmd('docker exec generator docker-entrypoint.sh ' . $cmd);
    }

    private function writeOptionsToTmpFile()
    {
        $this->tmpfile = tempnam("/tmp", "json");
        $handle = fopen($this->tmpfile, "w");
        fwrite(
            $handle,
            json_encode($this->getConfig())
        );
        fclose($handle);
    }

    private function getConfig() {
        # get all available options with curl http://127.0.0.1:8080/api/gen/clients/php
        $options = [
            "gitUserId" => "processmaker",
            "gitRepoId" => "sdk-" . $this->lang,
            "packageName" => "pmsdk",
            "appDescription" => "SDK Client for the ProcessMaker App",
            "infoUrl" => "https://github.com/ProcessMaker/processmaker",
            "infoEmail" => "info@processmaker.com",
        ];

        if (isset($this->config()['options'])) {
            $options = array_merge($options, $this->config()['options']);
        }
        return $options;
    }

    private function config()
    {
        return config('script-runners.' . $this->lang);
    }

    private function runCmd($cmd)
    {
        $this->log("Running: $cmd");
        exec($cmd . " 2>&1", $output, $returnVal);
        $output = implode("\n", $output);
        if ($returnVal) {
            $this->stopContainer();
            throw new Exception("Cmd returned: $returnVal " . $output);
        }
        $this->log("Got: '$output'");
        return $output;
    }

    private function log($message)
    {
        if ($this->debug) {
            echo "$message\n";
        }
    }

    private function commentErroneousCode($folder)
    {
        if ($this->lang === 'lua') {
            $this->runCmd("find {$folder} -name '*.lua' -exec sed -i -E 's/(req\.readers:upsert.*)/-- \\1/g' {} \;");
        }
    }

    private function addMissingDependency($folder)
    {
        if ($this->lang !== 'java') {
            return;
        }
        $file = "{$folder}/pom.xml";
        $dom = new \DOMDocument();
        $dom->load($file);
        $deps = $dom->getElementsByTagName('dependencies')[0];
        $dep = $dom->createDocumentFragment();
        $dep->appendXML('
            <dependency>
                <groupId>joda-time</groupId>
                <artifactId>joda-time</artifactId>
                <version>2.3</version>
            </dependency>
        ');
        $deps->appendChild($dep);
        file_put_contents($file, $dom->saveXml());
    }
    
    private function removeDateTime($folder)
    {
        if ($this->lang === 'csharp') {
            // $this->runCmd("find {$folder} -name '*.cs' -exec sed -i -E 's/DateTime\?/DateTime/g' {} \;");
            unlink("{$folder}/src/ProcessMakerSDK/Model/DateTime.cs");
        }
    }
}
