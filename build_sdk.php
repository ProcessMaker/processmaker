<?php
require __DIR__ . '/vendor/autoload.php';

class BuildSdk {
    private $client;
    private $debug;
    private $image = "openapitools/openapi-generator-online:v3.3.4";
    private $lang = "php";

    public function __construct($debug = false)
    {
        $this->client = new GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
        $this->debug = $debug;
    }

    public function run()
    {
        $running = $this->runCmd("docker container ls -aq --filter='ancestor=$this->image'");
        if (!empty($running)) {
            $running = str_replace("\n", " ", $running);
            $this->runCmd("docker container stop $running");
            $this->runCmd("docker container rm $running");
        }

        $this->runCmd('docker pull ' . $this->image);
        $cid = $this->runCmd('docker run -d -p 8888:8080 -e GENERATOR_HOST=http://localhost:8888 ' . $this->image);
        $this->log("sleeping 5....");


        // i=0; while [ $(docker inspect -f {{.State.Running}} $CID) != "true" ]; do if (($i > 10)); then exit 1; fi; sleep 1; ((i=$i+1)); done

        $i = 0;
        while(true) {
            sleep(2);
            try {
                $this->client->get("http://localhost:8888/api/gen/clients/$this->lang");
                break;
            } catch(GuzzleHttp\Exception\RequestException $e) {
                $this->log("Not ready, trying again in 2 seconds");
            }
            if ($i > 30) { 
                die("ERROR: Took too long to start up.");
            }
            $i++;
        }

        $response = $this->client->post("http://localhost:8888/api/gen/clients/{$this->lang}", [
            GuzzleHttp\RequestOptions::JSON => $this->requestBody(),
        ]);
        $json = json_decode($response->getBody(), true);
        $link = $json['link'];

        print_r($json['link']);

        $getter = new GuzzleHttp\Client();
        $getter->get($link, ['sink' => 'api.zip']);

        $zip = new ZipArchive;
        $res = $zip->open('api.zip');
        $zip->extractTo('.');
        $zip->close();
        unlink('api.zip');

        $this->runCmd("rm -rf storage/api && mkdir -p storage/api");
        $this->runCmd("mv {$this->lang}-client storage/api/SwaggerClient-php");
    }

    
    private function requestBody()
    {
        # get all available options with curl http://locahost:8888/api/gen/clients/php
        return [
            "options" => [
                "gitUserId" => "ProcessMaker",
                "gitRepoId" => "bpm-php-sdk",
            ],
            "spec" => $this->apiJsonDoc(),
        ];
    }

    private function apiJsonDoc()
    {
        return json_decode(
            file_get_contents(__DIR__ . "/storage/api-docs/api-docs.json")
        );
    }

    private function runCmd($cmd)
    {
        $this->log("Running: $cmd");
        exec($cmd . " 2>&1", $output, $returnVal);
        $output = implode("\n", $output);
        if ($returnVal) {
            die("ERROR: " . $output);
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

(new BuildSdk(true))->run();



