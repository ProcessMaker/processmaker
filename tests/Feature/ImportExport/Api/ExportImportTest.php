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
        $screen = factory(Screen::class)->create();

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
        $screen = factory(Screen::class)->create(['title' => 'Screen']);

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
        $fileName = "{$screen->title}.json";
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', "attachment; filename={$fileName}");

        // Ensure it's encrypted.
        $data = json_decode($response->streamedContent(), true);
        $this->assertEquals(true, $data['encrypted']);
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
        $screen = factory(Screen::class)->create();
        $exporter = new Exporter();
        $exporter->exportScreen($screen);

        // Create fake file upload.
        $content = json_encode($exporter->payload());
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'exported.json', null, null, null, true);

        return [
            $file,
        ];
    }
}
