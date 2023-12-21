<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\WizardTemplate;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class WizardTemplatesTest extends TestCase
{
    use RequestHelper;

    public function testGetWizardTemplates()
    {
        $total = 20;
        WizardTemplate::factory()->count($total)->create();

        $params = [
            'order_by' => 'id',
            'order_direction' => 'asc',
            'per_page' => 10,
        ];
        $route = route('api.wizard-templates.index', $params);
        $response = $this->apiCall('GET', $route);

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJson([
            'meta' => [
                'per_page' => $params['per_page'],
                'total' => $total,
            ],
        ]);
    }

    public function testItCanAddFilesFromUrlToMediaCollection()
    {
        // Create fake files.
        Storage::fake('public');
        $directoryName = 'images';
        $filesToCreate = 3;
        for ($i = 0; $i < $filesToCreate; $i++) {
            UploadedFile::fake()->image("test_image{$i}.jpg")->storeAs($directoryName, "test_image{$i}.jpg", 'public');
        }

        // Add files to media collection.
        $directory = Storage::disk('public')->path($directoryName);
        $wizardTemplate = WizardTemplate::factory()->create();
        $wizardTemplate->addFilesToMediaCollection($directory);

        $this->assertCount($filesToCreate, $wizardTemplate->getMedia($directoryName));
        $this->assertDatabaseHas('media', [
            'model_type' => WizardTemplate::class,
            'model_id' => $wizardTemplate->id,
            'collection_name' => $directoryName,
            'name' => 'test_image0',
        ]);
    }
}
