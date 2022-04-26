<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use org\bovigo\vfs\vfsStream;

class LogsTest extends TestCase
{
    use RequestHelper;

    private $params = [
        'search' => 'Process created:,Process completed:',
        'all' => 0
    ];

    public function setUpLogs()
    {
        $vfs = vfsStream::setup('storage');
        $storageDir = $vfs->url();
        app()->useStoragePath($storageDir);
        mkdir($storageDir . '/logs');

        $log = <<<LOG
            [2022-04-26 00:04:54] local.INFO: Process created: {"id":9,"timestamp":"0.00251600 1650931494"} 
            [2022-04-26 00:04:54] local.DEBUG: Sending request started notification to  (users: )  
            [2022-04-26 00:04:54] local.INFO: Activity activated: {"id":26,"status":"ACTIVE","index":0}  
            [2022-04-26 00:04:54] local.INFO: Dispatch a script task: node_3 #26  
            [2022-04-26 00:04:56] local.INFO: ActivityClosed: {"id":26,"status":"CLOSED","index":0,"element_ref":"node_3"}  
            [2022-04-26 00:04:56] local.INFO: Process completed: {"id":9,"timestamp":"0.59631100 1650931496"} 
            [2022-04-26 00:04:56] local.DEBUG: Sending request completed notification to  (users: ) 
            LOG;
        $date = new \DateTime();
        $logFilePath = $storageDir . '/logs/processmaker-' . $date->format('Y-m-d') . '.log';
        file_put_contents($logFilePath, $log);

        $date->modify('-1 day');
        $logFilePath = $storageDir . '/logs/processmaker-' . $date->format('Y-m-d') . '.log';
        file_put_contents($logFilePath, $log);
    }

    public function testWithLatestLogFile()
    {
        $result = $this->apiCall("get", route('api.logs.index'), $this->params);
        $result = $result->json();

        $this->assertCount(2, $result);
        $this->assertEquals('Process created:', $result[0]['search']);
        $this->assertEquals(9, $result[0]['id']);
        $this->assertEquals('0.00251600 1650931494', $result[0]['timestamp']);
        $this->assertEquals('Process completed:', $result[1]['search']);
        $this->assertEquals(9, $result[1]['id']);
        $this->assertEquals('0.59631100 1650931496', $result[1]['timestamp']);
    }

    public function testWithAllLogFiles()
    {
        $this->params['all'] = 1;
        $result = $this->apiCall("get", route('api.logs.index'), $this->params);
        $result = $result->json();
        $this->assertCount(4, $result);
    }
}