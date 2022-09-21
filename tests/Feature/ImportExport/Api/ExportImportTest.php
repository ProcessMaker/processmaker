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

    public function testDownloadFile()
    {
        $screen = factory(Screen::class)->create();

        $route = route('api.export.download', [
            'type' => 'screen',
            'id' => $screen->id,
        ], [
            'password' => null,
        ]);
        $response = $this->apiCall('GET', $route);

        // Ensure we can download the exported file.
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=export.json');
    }

    public function testImportPreview()
    {
        $screen = factory(Screen::class)->create();
        $exporter = new Exporter();
        $exporter->exportScreen($screen);

        // Create fake file upload.
        $content = json_encode($exporter->payload());
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'exported.json', null, null, null, true);

        $response = $this->apiCall('POST', route('api.import.preview'), [
            'file' => $file,
            // 'password' => null,
        ]);

        $response->assertStatus(200);
        $data = $response->getData();
        $this->assertObjectHasAttribute('tree', $data);
        $this->assertObjectHasAttribute('manifest', $data);
    }
}
