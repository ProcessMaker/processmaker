<?php
namespace ProcessMaker;

use \Exception;
use \ZipArchive;
use function GuzzleHttp\json_decode;

class BuildSdk {
    private $rebuild = false;
    private $debug = false;
    private $image = "openapitools/openapi-generator-online:v4.0.2";
    private $lang = null;
    private $outputPath = null;
    private $jsonPath = null;

    public function __construct($jsonPath, $outputPath, $debug = false, $rebuild = false) {
        $this->jsonPath = $jsonPath;
        $this->outputPath = rtrim($outputPath, "/");
        $this->debug = $debug;
        $this->rebuild = $rebuild;
    }

    public function run()
    {
        $this->runChecks();
        $this->startBootSequence();

        $response = $this->docker($this->curlPost());

        $json = json_decode($response, true);
        if (!array_key_exists('link', $json)) {
            throw new Exception("Generator Error: " . $response);
        }
        $link = $json['link'];

        $zip = $this->getZip($link);
        $folder = $this->unzip($zip);
        $this->commentErroneousCode($folder);
        $this->addMissingDependency($folder);
        $this->runCmd("cp -rf {$folder}/. {$this->outputDir()}");
        $this->log("DONE. Api is at {$this->outputDir()}");
    }

    private function startBootSequence()
    {
        $existing = $this->existingContainer();

        if ($this->rebuild && $existing !== "") {
            $this->runCmd("docker container stop $existing || echo 'Container already stopped'");
            $this->runCmd("docker container rm $existing");
        }

        if ($existing === "" || $this->rebuild) {
            $this->runCmd('docker pull ' . $this->image);
            $cid = $this->runCmd('docker run -d --name generator -e GENERATOR_HOST=http://127.0.0.1:8080 ' . $this->image);
            $this->docker('apk add --update curl && rm -rf /var/cache/apk/*');
        } else {
            $this->runCmd("docker start generator || echo 'Generator already started'");
        }

        $this->runCmd('docker start generator || echo "Container already running"');

        $this->waitForBoot();
    }

    public function setLang($value)
    {
        if (!in_array($value, $this->getAvailableLanguages())) {
            throw new Exception("$value language is not supported");
        }
        $this->lang = $value;
    }

    public function getOptions()
    {
        if (!$this->lang) {
            throw new Exception("Language must be specified using setLang()");
        }
        $this->startBootSequence();
        return $this->docker("curl -s -S http://127.0.0.1:8080/api/gen/clients/{$this->lang}");
    }
    
    public function getAvailableLanguages()
    {
        $this->startBootSequence();
        $result = $this->docker("curl -s -S http://127.0.0.1:8080/api/gen/clients");
        return json_decode($result, true);
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

        if (json_decode($this->apiJsonRaw()) === null) {
            throw new Exception("File is not valid json: " . $this->jsonPath);
        }

    }

    private function outputDir()
    {
        return $this->outputPath;
    }

    private function waitForBoot()
    {
        $i = 0;
        while(true) {
            try {
                $this->docker("curl -s -S http://127.0.0.1:8080/api/gen/clients/{$this->lang}");
                break;
            } catch(Exception $e) {
                if (strpos($e->getMessage(), 'Connection refused') !== false) {
                    $this->log("Not ready, trying again in 2 seconds. " . $e->getMessage());
                } else {
                    throw $e;
                }
            }
            if ($i > 20) {
                throw new Exception("Took too long to start up.");
            }
            sleep(2);
            $i++;
        }
    }

    private function getZip($url)
    {
        $filename = "{$this->outputPath}/api.zip";
        $this->docker("curl -s -S $url > $filename");
        return $filename;
    }

    private function unzip($file)
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        $folder = explode('/', $zip->statIndex(0)['name'])[0];
        $this->runCmd('rm -rf /tmp/processmaker-sdk-tmp');
        $zip->extractTo("/tmp/processmaker-sdk-tmp");
        $zip->close();
        unlink($file);
        return "/tmp/processmaker-sdk-tmp/{$this->lang}-client";
    }

    private function existingContainer()
    {
        return $this->runCmd("docker container ls -aq --filter='name=generator'");
    }

    private function curlPost()
    {
        return 'curl -s -S '
            . '-H "Accept: application/json" '
            . '-H "Content-Type: application/json" '
            . '-X POST -d ' . $this->cliBody() . ' '
            . 'http://127.0.0.1:8080/api/gen/clients/'.$this->lang;
    }

    private function docker($cmd)
    {
        return $this->runCmd('docker exec generator ' . $cmd);
    }

    private function cliBody()
    {
        return escapeshellarg(
            str_replace('"API-DOCS-JSON"', $this->apiJsonRaw(), $this->requestBody())
        );
    }

    private function requestBody()
    {
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

        return json_encode(['options' => $options, 'spec' => 'API-DOCS-JSON']);
    }

    private function config()
    {
        return config('script-runners.' . $this->lang);
    }

    private function apiJsonRaw()
    {
        return file_get_contents($this->jsonPath);
    }

    private function runCmd($cmd)
    {
        $this->log("Running: $cmd");
        exec($cmd . " 2>&1", $output, $returnVal);
        $output = implode("\n", $output);
        if ($returnVal) {
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
}
