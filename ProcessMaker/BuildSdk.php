<?php

namespace ProcessMaker;

use Exception;
use function GuzzleHttp\json_decode;
use ProcessMaker\Events\BuildScriptExecutor;
use ProcessMaker\Facades\Docker;
use ZipArchive;

class BuildSdk
{
    private $debug = true;

    private $image = 'openapitools/openapi-generator-cli';

    private $tag = 'v5.1.1';

    private $lang = null;

    private $outputPath = null;

    private $jsonPath = null;

    private $tmpfile = null;

    private $userId = null;

    public function __construct($jsonPath, $outputPath)
    {
        $this->jsonPath = $jsonPath;
        $this->outputPath = rtrim($outputPath, '/');
    }

    public function run()
    {
        $folder = '/tmp/sdk-' . $this->lang;
        $this->runCmd('rm -rf ' . $folder);

        $this->writeOptionsToTmpFile();

        $this->startContainer();
        $this->cp($this->jsonPath, 'generator:/api-docs.json');
        $this->cp($this->tmpfile, 'generator:/config.json');
        $this->generator('validate -i /api-docs.json');
        $this->generator('generate -g ' . $this->lang . ' -i /api-docs.json -c /config.json -o /sdk');
        $this->cp('generator:/sdk', $folder);
        $this->stopContainer();

        $this->fixErroneousCode($folder); // lua and python
        $this->addMissingDependency($folder); // java
        $this->removeDateTime($folder); // csharp
        $this->runCmd("cp -rf {$folder}/. {$this->outputDir()}");

        return "DONE. Api is at {$this->outputDir()}";
    }

    public function setUserId($userId)
    {
        if (!is_numeric($userId)) {
            throw new Exception('User id must be a number');
        }
        $this->userId = $userId;
    }

    private function cp($from, $to)
    {
        $this->runCmd(Docker::command() . ' cp ' . $from . ' ' . $to);
    }

    private function imageWithTag()
    {
        return $this->image . ':' . $this->tag;
    }

    private function startContainer()
    {
        $this->runCmd(Docker::command() . " run -t -d --entrypoint '/bin/sh' --name generator " . $this->imageWithTag());
    }

    private function stopContainer()
    {
        $this->runCmd(Docker::command() . ' kill generator 2>&1 || true');
        $this->runCmd(Docker::command() . ' rm generator 2>&1 || true');
    }

    public function setLang($value)
    {
        $langs = $this->getAvailableLanguages();
        if (!in_array($value, $langs)) {
            throw new Exception("$value language is not supported. Must be one of these: " . implode(',', $langs));
        }
        $this->lang = $value;
    }

    public function getOptions()
    {
        if (!$this->lang) {
            throw new Exception('Language must be specified using setLang()');
        }

        return $this->runCmd(Docker::command() . ' run ' . $this->imageWithTag() . ' config-help -g ' . $this->lang);
    }

    public function getAvailableLanguages()
    {
        $result = $this->runCmd(Docker::command() . ' run ' . $this->imageWithTag() . ' list -s');

        return explode(',', $result);
    }

    private function runChecks()
    {
        if (!$this->lang) {
            throw new Exception('Language must be specified using setLang()');
        }

        if (!is_dir($this->outputPath)) {
            throw new Exception("{$this->outputPath} is not a valid directory");
        }

        if (!is_writable($this->outputPath)) {
            throw new Exception('Folder is not writeable: ' . $this->outputPath);
        }

        if (!is_file($this->jsonPath) || !is_readable($this->jsonPath)) {
            throw new Exception('Json file does not exist or can not be read: ' . $this->jsonPath);
        }
    }

    private function outputDir()
    {
        return $this->outputPath;
    }

    private function generator($cmd)
    {
        return $this->runCmd(Docker::command() . ' exec generator docker-entrypoint.sh ' . $cmd);
    }

    private function writeOptionsToTmpFile()
    {
        $this->tmpfile = tempnam('/tmp', 'json');
        $handle = fopen($this->tmpfile, 'w');
        fwrite(
            $handle,
            json_encode($this->getConfig())
        );
        fclose($handle);
    }

    private function getConfig()
    {
        // get all available options with curl http://127.0.0.1:8080/api/gen/clients/php
        $options = [
            'gitUserId' => 'processmaker',
            'gitRepoId' => 'sdk-' . $this->lang,
            'packageName' => 'pmsdk',
            'appDescription' => 'SDK Client for the ProcessMaker App',
            'infoUrl' => 'https://github.com/ProcessMaker/processmaker',
            'infoEmail' => 'info@processmaker.com',
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
        if ($this->userId) {
            event(new BuildScriptExecutor("Running: $cmd\n", $this->userId, 'running'));
        } else {
            $this->log("Running: $cmd");
        }

        $dsc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open("($cmd) 2>&1", $dsc, $pipes);

        $output = '';
        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);

            if ($this->userId) {
                event(new BuildScriptExecutor($line, $this->userId, 'running'));
            }

            $output .= $line;
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnVal = proc_close($process);

        if ($returnVal) {
            $this->stopContainer();

            $message = "Cmd returned: $returnVal " . $output;

            if ($this->userId) {
                event(new BuildScriptExecutor($message, $this->userId, 'error'));
            }

            throw new Exception($message);
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

    private function fixErroneousCode($folder)
    {
        if ($this->lang === 'lua') {
            $this->runCmd("find {$folder} -name '*.lua' -exec sed -i -E 's/(req\.readers:upsert.*)/-- \\1/g' {} \;");
        }

        if ($this->lang === 'python') {
            // Replace \User with \\User since slash \U is unicode in python. Major slashitis.
            $this->runCmd(
                "find {$folder} -name '*.py' -exec " .
                "sed -i -E 's/ProcessMaker\\\Models\\\/ProcessMaker\\\\\\\Models\\\\\\\/g' {} \;"
            );
        }

        if ($this->lang === 'php') {
            $content = file_get_contents("{$folder}/lib/ObjectSerializer.php");
            $line = 'if (in_array($class, [\'DateTime';
            $insertAbove = '
                if ($class === "mixed") {
                    $jsonObj = json_decode($data, true);
                    if ($jsonObj === null && json_last_error() !== JSON_ERROR_NONE) {
                        return $data;
                    }
                    return $jsonObj;
                }
            ';
            $content = str_replace($line, $insertAbove . "\n\n" . $line, $content);
            file_put_contents("{$folder}/lib/ObjectSerializer.php", $content);
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
            unlink("{$folder}/src/ProcessMakerSDK/Model/DateTime.cs");
        }
    }
}
