<?php

namespace Tests\Feature\ImportExport\Api;

use Illuminate\Http\UploadedFile;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ExportImportTest extends TestCase
{
    use RequestHelper;

    public function testGetTree()
    {
        $screen = Screen::factory()->create();

        $route = route('api.export.tree', [
            'type' => 'screen',
            'id' => $screen->id,
        ]);
        $response = $this->apiCall('GET', $route);

        $response->assertStatus(200);
        $data = $response->getData();
        $this->assertObjectHasAttribute('tree', $data);
        $this->assertObjectHasAttribute('manifest', $data);
    }

    public function testDownloadExportFile()
    {
        $screen = Screen::factory()->create(['title' => 'Screen']);

        $response = $this->apiCall(
            'POST',
            route('api.export.download', [
                'type' => 'screen',
                'id' => $screen->id,
            ]),
            [
                'password' => 'foobar',
                'options' => [],
            ]
        );

        // Ensure we can download the exported file.
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', "attachment; filename={$screen->title}.json");

        // Ensure it's encrypted.
        $payload = json_decode($response->streamedContent(), true);
        $this->assertEquals(true, $payload['encrypted']);

        $headers = $response->headers;
        $exportInfo = json_decode($headers->get('export-info'), true)['exported'];
        $this->assertCount(1, $exportInfo['screens']);
        $this->assertEquals($screen->id, $exportInfo['screens'][0]);
        $this->assertCount(1, $exportInfo['screen_categories']);
        $this->assertEquals($screen->categories[0]->id, $exportInfo['screen_categories'][0]);
    }

    public function testImportPreview()
    {
        [$file] = $this->importFixtures();

        $response = $this->apiCall('POST', route('api.import.preview'), [
            'file' => $file,
            // 'password' => null,
            // 'options' => [],
        ]);

        $response->assertStatus(200);
        $data = $response->getData();
        $this->assertObjectHasAttribute('tree', $data);
        $this->assertObjectHasAttribute('manifest', $data);
    }

    public function testImport()
    {
        [$file] = $this->importFixtures();

        $response = $this->apiCall('POST', route('api.import.do_import'), [
            'file' => $file,
            // 'password' => null,
            // 'options' => [],
        ]);
        $response->assertStatus(200);
    }

    private function importFixtures()
    {
        $screen = Screen::factory()->create();
        $exporter = new Exporter();
        $exporter->exportScreen($screen);

        // Create fake file upload.
        $payload = $exporter->payload();
        $content = json_encode($payload);
        $fileName = tempnam(sys_get_temp_dir(), $payload['name']);
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, $payload['name'] . '.json', null, null, null, true);

        return [
            $file,
        ];
    }
}
