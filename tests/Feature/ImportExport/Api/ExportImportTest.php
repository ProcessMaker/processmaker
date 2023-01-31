<?php

namespace Tests\Feature\ImportExport\Api;

use Illuminate\Http\UploadedFile;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\Screen;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ExportImportTest extends TestCase
{
    use RequestHelper;
    use HelperTrait;

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
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('manifest', $json);
    }

    public function testImport()
    {
        [$file] = $this->importFixtures();

        $response = $this->apiCall('POST', route('api.import.do_import'), [
            'file' => $file,
            'password' => null,
            'options' => $this->makeOptions(),
        ]);
        $response->assertStatus(200);
    }

    public function testHandleDuplicateAttributes()
    {
        [$file, $screen, $nestedScreen] = $this->importFixtures();

        // Test that there is an unsaved screen in the manifest with a duplicate name
        // so delete it from the target instance here
        $nestedScreenUuid = $nestedScreen->uuid;
        $nestedScreen->delete();

        $initialScreenCount = Screen::count();

        // Update the existing screen, no change to title
        $response = $this->apiCall('POST', route('api.import.do_import'), [
            'file' => $file,
            'password' => null,
            'options' => $this->makeOptions([
                $screen->uuid => ['mode' => 'copy'],
                $nestedScreenUuid => ['mode' => 'update'],
            ]),
        ]);
        $response->assertStatus(200);

        // Assert we added the child screen and the copied parent screen
        $this->assertEquals($initialScreenCount + 2, Screen::count());

        // Original parent screen on target instance
        $this->assertDatabaseHas('screens', ['title' => 'Exported Screen']);
        // Imported "copy" parent screen - gets the first auto-increment to 1
        $this->assertDatabaseHas('screens', ['title' => 'Exported Screen 1']);
        // Imported nested screen, originally called "Exported Screen 1", gets incremented to 2
        $this->assertDatabaseHas('screens', ['title' => 'Exported Screen 2']);
    }

    private function importFixtures()
    {
        $nestedScreen = Screen::factory()->create([
            'title' => 'Exported Screen 1',
            'description' => 'child',
            'screen_category_id' => null,
        ]);
        $config = [
            [
                'items' => [
                    [
                        'component' => 'FormNestedScreen',
                        'config' => [
                            'screen' => (int) $nestedScreen->id,
                        ],
                    ],
                ],
            ],
        ];
        $screen = Screen::factory()->create([
            'title' => 'Exported Screen',
            'config' => $config,
            'description' => 'parent',
            'screen_category_id' => null,
        ]);
        $exporter = new Exporter();
        $exporter->exportScreen($screen);

        // Create fake file upload.
        $payload = $exporter->payload();
        $content = json_encode($payload);
        $file = UploadedFile::fake()->createWithContent($payload['name'] . '.json', $content);

        return [
            $file, $screen, $nestedScreen,
        ];
    }
}
