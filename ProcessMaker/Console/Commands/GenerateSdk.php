<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GenerateSdk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bpm:generate-sdk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the SDK from swagger docs';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $host = "127.0.0.1";
        \Artisan::call('l5-swagger:generate');
        $running_cid =
            exec('docker ps -q  ' .
                 '--filter=ancestor=openapitools/openapi-generator-online');

        if (ctype_alnum($running_cid)) {
            $cid = $running_cid;
            echo "Container is alredy running. Using container $cid\n";
        } else {
            $cid = exec('docker run -d -p 8888:8080 ' .
                        "-e GENERATOR_HOST=http://${host}:8888 " .
                        'openapitools/openapi-generator-online');
            if (!ctype_alnum($cid)) {
                echo "Could not get container id. Got: $cid \n";
                exit(1);
            }
        }

        $client = new Client(); // Should this be injected?
        $spec_file = base_path() . '/storage/api-docs/api-docs.json';
        $spec_json = ['spec' => json_decode(file_get_contents($spec_file))];

        $i = 0;
        $result = false;
        
        // Lets wait for the docker image to by ready by hammering its API
        while(!$result) {
            try {
                $result = $client->post(
                    "http://${host}:8888/api/gen/clients/php",
                    ['json' => $spec_json]
                );
            } catch(RequestException $e) {
                $result = false;
                sleep(1);
                if ($i >= 30) {
                    echo "OpenAPI Container failed to start: Got: $e\n";
                    exit(1);
                }
                $i++;
            }
        }

        $link = json_decode($result->getBody())->link;
        if (!$link) { 
            echo "Failed to get link from json response\n";
            exit(1);
        }

        $zip_file = '/tmp/pmsdk.zip';
        $client->get($link, ['sink' => $zip_file]);

        $zip = new \ZipArchive;
        if ($zip->open($zip_file) === true) {
            $zip->extractTo(base_path() . '/storage/sdk');
            $zip->close();
        } else {
            echo "Invalid zip file: $zip_file\n";
            exit(1);
        }
        echo "SDK Generated Successfully\n";
    }
}
