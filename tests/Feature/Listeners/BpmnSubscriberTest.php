<?php

namespace ProcessMaker\Listeners;

use Carbon\Carbon;
use DateTime;
use ProcessMaker\Listeners\BpmnSubscriber;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\TestCase;

class BpmnSubscriberTest extends TestCase
{
    public function testErrorHandlerRegistration()
    {
        $subscriber = new BpmnSubscriber();
        $this->assertNull($subscriber->registerErrorHandler(null, null));
    }

    public function testErrorHandler()
    {
        $path = storage_path('app/private');
        $errorFile = $path.'/unhandled_error.txt';
        $this->removeFileIfExists($errorFile);

        $subscriber = new BpmnSubscriber();
        $token = new ProcessRequestToken();
        $token->id = 10;
        $subscriber->errorHandler($path, $token);

        $this->assertFileExists($path.'/unhandled_error.txt');

        $fileContent = file_get_contents($errorFile);

        $this->assertEquals('10', trim($fileContent));

        // write another token
        $token2 = new ProcessRequestToken();
        $token2->id = 20;
        $subscriber->errorHandler($path, $token2);
        $fileContent2 = file_get_contents($errorFile);

        $this->assertEquals("10\n20\n", $fileContent2);

        $this->removeFileIfExists($errorFile);
    }

    /**
     * @param string $errorFile
     */
    public function removeFileIfExists(string $errorFile): void
    {
        if (file_exists($errorFile)) {
            unlink($errorFile);
        }
    }
}
