<?php
namespace ProcessMaker;

use \Exception;
use \ZipArchive;

class BuildSdk {
    private $rebuild = false;
    private $debug = false;
    private $image = "openapitools/openapi-generator-online:v4.0.0-beta2";
    private $lang = null;
    private $supportedLangs = ['php', 'lua'];
    private $basePath = null;

    public function __construct($basePath, $debug = false, $rebuild = false) {
        if (!is_dir($basePath)) {
            throw new Exception("$basePath is not a valid directory");
        }
        $this->basePath = $basePath;
        $this->debug = $debug;
        $this->rebuild = $debug;
    }

    public function run()
    {
        if (!$this->lang) {
            throw new Exception("Language must be specified using setLang()");
        }
        $existing = $this->existingContainers();
        if (!empty($existing) && $this->rebuild) {
            $existing = str_replace("\n", " ", $existing);
            $this->runCmd("docker container stop $existing");
            $this->runCmd("docker container rm $existing");
            $existing = [];
        }

        if (empty($existing) || $this->rebuild) {
            $this->runCmd('docker pull ' . $this->image);
            $cid = $this->runCmd('docker run -d --name generator -e GENERATOR_HOST=http://127.0.0.1:8080 ' . $this->image);
            sleep(5);
            $this->docker('apk add --update curl && rm -rf /var/cache/apk/*');
        }

        $this->waitForBoot();

        $response = $this->docker($this->curlPost());

        $json = json_decode($response, true);
        $link = $json['link'];

        $zip = $this->getZip($link);
        $folder = $this->unzip($zip);

        $this->runCmd("mkdir -p {$this->basePath}/storage/api");
        $dest = "{$this->basePath}/storage/api/";
        $this->runCmd("mv -f $folder $dest");
        $this->log("DONE. Api is at $dest");
    }

    public function setLang($value) {
        if (!in_array($value, $this->supportedLangs)) {
            throw new Exception("$value language is not supported");
        }
        $this->lang = $value;
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
        $filename = "{$this->basePath}/api.zip";
        $this->docker("curl -s -S $url > $filename");
        return $filename;
    }

    private function unzip($file)
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        $folder = explode('/', $zip->statIndex(0)['name'])[0];
        $zip->extractTo($this->basePath);
        $zip->close();
        unlink($file);
        return "{$this->basePath}/$folder";
    }

    private function existingContainers()
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
        return json_encode([
            "options" => [
                "gitUserId" => "ProcessMaker",
                "gitRepoId" => "bpm-".$this->lang."-sdk",
            ],
            "spec" => "API-DOCS-JSON",
        ]);
    }

    private function apiJsonRaw()
    {
        return file_get_contents($this->basePath . "/storage/api-docs/api-docs.json");
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
}
