<?php

namespace Tests\Feature\Templates\Api;

use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Templates\HelperTrait;
use Tests\TestCase;

class ScreenTemplateTest extends TestCase
{
    use RequestHelper;
    use HelperTrait;
    use WithFaker;

    public function testCreateScreenTemplate()
    {
        $screenCategoryId = ScreenCategory::factory()->create()->id;
        $screen = Screen::factory()->create([
            'title' => 'Test Screen',
            'type' => 'FORM',
            'screen_category_id' => $screenCategoryId,
        ]);
        $screenId = $screen->id;

        $route = route('api.template.store', ['screen', $screenId]);
        $data = [
            'name' => 'Test Screen Template Creation',
            'description' => 'Test Screen Template Description',
            'screen_category_id' => $screenCategoryId,
            'version'   => '1.0.0',
            'asset_id' => $screenId,
            'saveAssetsMode' => 'saveAllAssets',
        ];
        $response = $this->apiCall('POST', $route, $data);

        // Assert response status
        $response->assertStatus(200);

        // Assert that our database has the screen template we need
        $this->assertDatabaseHas('screen_templates', ['name' => 'Test Screen Template Creation']);
    }

    public function testUpdateScreenTemplate()
    {
        $screenTemplateId = ScreenTemplates::factory()->create()->id;

        $route = route('api.template.settings.update', ['screen', $screenTemplateId]);
        $data = [
            'name' => 'Test Screen Template Update',
            'description' => 'Test Screen Template Updated Description',
            'version' => '1.0.1',
        ];
        $response = $this->apiCall('PUT', $route, $data);
        // Assert response status
        $response->assertStatus(200);

        // Assert that our database has the screen template we need
        $this->assertDatabaseHas('screen_templates', ['name' => 'Test Screen Template Update']);
    }

    // public function testDeleteScreenTemplate()
    // {

    // }
}
