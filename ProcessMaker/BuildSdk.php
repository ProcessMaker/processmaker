<?php
namespace ProcessMaker;

use \Exception;
use \ZipArchive;

class BuildSdk {
    private $rebuild = false;
    private $debug = false;
    private $image = "openapitools/openapi-generator-online:v4.0.0-beta2";
    private $lang = null;
    private $supportedLangs = ['php', 'lua', 'typescript-node', 'node'];
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

        $existing = $this->existingContainer();

        if ($this->rebuild && $existing !== "") {
            $this->runCmd("docker container stop $existing || echo 'Container already stopped'");
            $this->runCmd("docker container rm $existing");
        }
        
        if ($existing === "" || $this->rebuild) {
            $this->runCmd('docker pull ' . $this->image);
            $cid = $this->runCmd('docker run -d --name generator -e GENERATOR_HOST=http://127.0.0.1:8080 ' . $this->image);
            $this->docker('apk add --update curl && rm -rf /var/cache/apk/*');
        }
        
        $this->runCmd('docker start generator || echo "Container already running"');
        $this->waitForBoot();

        $response = $this->docker($this->curlPost());

        $json = json_decode($response, true);
        if (!array_key_exists('link', $json)) {
            throw new Exception("Generator Error: " . $response);
        }
        $link = $json['link'];

        $zip = $this->getZip($link);
        $folder = $this->unzip($zip);
        $this->runCmd("cp -rf {$folder}/. {$this->outputPath}");
        $this->log("DONE. Api is at {$this->outputPath}");
    }

    public function setLang($value)
    {
        if (!in_array($value, $this->supportedLangs)) {
            throw new Exception("$value language is not supported");
        }
        $this->lang = $value;
    }

    public function getOptions()
    {
        if (!$this->lang) {
            throw new Exception("Language must be specified using setLang()");
        }
        $this->waitForBoot();
        return $this->docker("curl -s -S http://127.0.0.1:8080/api/gen/clients/{$this->lang}");
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

        if (!is_file($this->jsonPath) || !is_readable($this->jsonPath)) {
            throw new Exception("Json file does not exist or can not be read: " . $this->jsonPath);
        }

        if (json_decode($this->apiJsonRaw()) === null) {
            throw new Exception("File is not valid json: " . $this->jsonPath);
        }

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
        $this->runCmd('rm -rf /tmp/pm4-sdk-tmp');
        $zip->extractTo("/tmp/pm4-sdk-tmp");
        $zip->close();
        unlink($file);
        return "/tmp/pm4-sdk-tmp/{$this->generatorLang()}-client";
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
            . 'http://127.0.0.1:8080/api/gen/clients/' . $this->generatorLang();
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
            "gitUserId" => "ProcessMaker",
            "gitRepoId" => "pm4-sdk-" . $this->lang,
            "appDescription" => "SDK Client for the ProcessMaker v4 App",
            "infoUrl" => "https://github.com/ProcessMaker/bpm",
            "infoEmail" => "info@processmaker.com",
        ];
        return json_encode([
            "options" => array_merge($options, $this->options()), 
            "spec" => "API-DOCS-JSON",
        ]);
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

    private function options()
    {
        return config("script-runners.{$this->lang}.sdk_build_options");
    }

    private function generatorLang()
    {
        if ($this->lang == 'node') {
            return 'javascript';
        }
        return $this->lang;
    }
}
