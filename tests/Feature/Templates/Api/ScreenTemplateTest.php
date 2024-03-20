<?php

namespace Tests\Feature\Templates\Api;

use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenTemplateTest extends TestCase
{
    use RequestHelper;
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
            'unique_template_id' => 'test-template-unique-id',
            'media_collection' => 'test-media-collection',
            'name' => 'Test Screen Template Creation',
            'thumbnails' => '[]',
            'description' => 'Test Screen Template Description',
            'screen_category_id' => $screenCategoryId,
            'is_public' => false,
            'version'   => '1.0.0',
            'asset_id' => $screenId,
            'screenType' => $screen->type,
            'saveAssetsMode' => 'saveAllAssets',
        ];
        $response = $this->apiCall('POST', $route, $data);

        // Assert successful response status
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

        // Assert that our database has the screen template we updated
        $this->assertDatabaseHas('screen_templates', ['name' => 'Test Screen Template Update']);
    }

    public function testDeleteScreenTemplate()
    {
        $screenTemplateId = ScreenTemplates::factory()->create()->id;
        $route = route('api.template.delete', ['screen', $screenTemplateId]);

        $response = $this->apiCall('DELETE', $route);
        // Assert response status
        $response->assertStatus(200);

        // Assert that our database is missing the screen template we deleted
        $this->assertDatabaseMissing('screen_templates', ['id' => $screenTemplateId]);
    }

    public function testPublishScreenTemplateWithoutPermissions()
    {
        $screenTemplateId = ScreenTemplates::factory()->create()->id;
        $user = User::factory()->create(['is_administrator' => false]);
        $route = route('api.template.publishTemplate', ['screen', $screenTemplateId]);

        // Our user should not have permissions, so this should return 403.
        $this->assertFalse($user->hasPermission('publish-screen-templates'));
        $response = $this->actingAs($user, 'api')->call('POST', $route);
        $response->assertStatus(403);
    }

    public function testPublishScreenTemplateWithPermissions()
    {
        $screenTemplateId = ScreenTemplates::factory()->create()->id;
        $user = User::factory()->create(['is_administrator' => false]);
        $permission = 'publish-screen-templates';
        $route = route('api.template.publishTemplate', ['screen', $screenTemplateId]);

        // Attach the permission to our user.
        $user->permissions()->sync([Permission::byName($permission)->id]);
        $user->save();
        $user->refresh();

        // Our user now has permission, so this should return 200.
        $this->assertTrue($user->hasPermission($permission));
        $response = $this->actingAs($user, 'api')->call('POST', $route);
        $response->assertStatus(200);

        // Check that the screen template was updated successfully
        $screenTemplate = ScreenTemplates::where('id', $screenTemplateId)->first();
        $this->assertSame($screenTemplate->is_public, 1);
    }

    public function testCreateScreenFromTemplate()
    {
        $screenTemplateId = ScreenTemplates::factory()->create()->id;
        $screen_category_id = ScreenCategory::factory()->create()->id;
        $user = User::factory()->create();

        $route = route('api.template.create', ['screen', $screenTemplateId]);
        $data = [
            'title' => 'Test Screen Creation',
            'description' => 'Test Screen Creation from Template',
            'screen_category_id' => $screen_category_id,
            'type' => 'FORM',
            'templateId' => $screenTemplateId,
        ];
        $response = $this->actingAs($user, 'api')->call('POST', $route, $data);
        $response->assertStatus(200);
    }
}
