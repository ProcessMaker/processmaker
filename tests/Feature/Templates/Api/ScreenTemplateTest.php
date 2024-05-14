<?php

namespace Tests\Feature\Templates\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use ProcessMaker\ImportExport\Exporters\ScreenTemplatesExporter;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\User;
use ProcessMaker\Package\Projects\Models\Project;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Templates\HelperTrait;
use Tests\TestCase;

class ScreenTemplateTest extends TestCase
{
    use RequestHelper;
    use WithFaker;
    use HelperTrait;

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
            'screen_type' => $screen->type,
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
        $nonAdminUser = User::factory()->create(['is_administrator' => false]);
        $screenTemplateId = ScreenTemplates::factory()->create([
            'user_id' => $nonAdminUser->id,
        ])->id;

        $route = route('api.template.settings.update', ['screen', $screenTemplateId]);
        $data = [
            'name' => 'Test Screen Template Update',
            'description' => 'Test Screen Template Updated Description',
            'version' => '1.0.1',
            'template_media' => [],
        ];
        $response = $this->actingAs($nonAdminUser, 'api')->call('PUT', $route, $data);

        // Assert that our database has the screen template we updated.
        $response->assertStatus(200);
        $this->assertDatabaseHas('screen_templates', [
            'name' => 'Test Screen Template Update',
            'user_id' => $nonAdminUser->id,
        ]);

        // Update the same template with the Admin user.
        $adminUser = User::factory()->create(['is_administrator' => true]);
        $data = [
            'name' => 'Test Screen Template Update by Admin',
            'description' => 'Test Screen Template Updated Description by Admin',
            'version' => '1.0.2',
        ];
        $response = $this->actingAs($adminUser, 'api')->call('PUT', $route, $data);
        $response->assertStatus(200);

        $this->assertDatabaseHas('screen_templates', [
            'name' => 'Test Screen Template Update by Admin',
            'user_id' => $nonAdminUser->id,
        ]);
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
        $user = User::factory()->create();
        $defaultScreenTemplate = ScreenTemplates::factory()->withCustomCss()->create([
            'name' => 'Default Screen Template',
            'is_default_template' => true,
        ]);
        $screenTemplate = ScreenTemplates::factory()->create([
            'name' => 'Screen Template',
            'is_default_template' => false,
        ]);
        $screenCategory = ScreenCategory::factory()->create();

        $route = route('api.template.create', ['screen', $screenTemplate->id]);
        $data = [
            'title' => 'Test Screen Creation',
            'description' => 'Test Screen Creation from Template',
            'screen_category_id' => $screenCategory->id,
            'type' => 'FORM',
            'templateId' => $screenTemplate->id,
            'defaultTemplateId' => $defaultScreenTemplate->id,
            'is_public' => true,
        ];
        $response = $this->actingAs($user, 'api')->call('POST', $route, $data);
        $response->assertStatus(200);

        // The new screen should not have custom_css because the selected screenTemplate does not have custom_css.
        $newScreen = Screen::where('title', 'Test Screen Creation')->first();
        $this->assertNull($newScreen->custom_css);
    }

    public function testCreateScreenFromTemplateWithDefault()
    {
        $user = User::factory()->create();
        $screenCategory = ScreenCategory::factory()->create();
        $sharedScreenTemplate = ScreenTemplates::factory()->create([
            'name' => 'My Shared Template',
            'is_public' => true,
            'is_default_template' => true,
        ]);
        $myScreenTemplate = ScreenTemplates::factory()->create([
            'name' => 'My Template',
            'is_public' => false,
            'is_default_template' => true,
        ]);

        // Create a screen from a public template.
        $data = [
            'title' => $this->faker->unique()->name(),
            'type' => 'FORM',
            'description' => $this->faker->sentence(),
            'is_public' => false,
            'screen_category_id' => $screenCategory->id,
            'defaultTemplateId' => $sharedScreenTemplate->id,
            'templateId' => $sharedScreenTemplate->id,
        ];
        $route = route('api.template.create', ['screen', $sharedScreenTemplate->id]);
        $response = $this->actingAs($user, 'api')->call('POST', $route, $data);
        $response->assertStatus(200);

        // Create a screen from my template.
        $data = [
            'title' => $this->faker->unique()->name(),
            'type' => 'FORM',
            'description' => $this->faker->sentence(),
            'is_public' => false,
            'screen_category_id' => $screenCategory->id,
            'defaultTemplateId' => $myScreenTemplate->id,
            'templateId' => $sharedScreenTemplate->id,
        ];
        $route = route('api.template.create', ['screen', $myScreenTemplate->id]);
        $response = $this->actingAs($user, 'api')->call('POST', $route, $data);
        $response->assertStatus(200);

        $this->assertDatabaseHas('screen_templates', [
            'name' => 'My Shared Template',
            'is_public' => 1,
            'is_default_template' => 1,
        ]);
        $this->assertDatabaseHas('screen_templates', [
            'name' => 'My Template',
            'is_public' => 0,
            'is_default_template' => 1,
        ]);
    }

    public function testCreateScreenFromTemplateWithProjects()
    {
        if (!class_exists(Project::class)) {
            $this->markTestSkipped('Package Projects is not installed.');
        }

        $project = Project::factory()->create();
        $screenTemplateId = ScreenTemplates::factory()->create()->id;
        $screen_category_id = ScreenCategory::factory()->create()->id;
        $user = User::factory()->create();

        $route = route('api.template.create', ['screen', $screenTemplateId]);
        $data = [
            'title' => $this->faker->unique()->name(),
            'description' => 'Test Screen Creation from Template',
            'screen_category_id' => $screen_category_id,
            'type' => 'FORM',
            'templateId' => $screenTemplateId,
            'projects' => implode(',', [$project->id]),
        ];

        $response = $this->actingAs($user, 'api')->call('POST', $route, $data);
        $response->assertStatus(200);
    }

    public function testShareScreenTemplate()
    {
        $screenTemplate = ScreenTemplates::factory()->create(['is_public' => false]);

        $route = route('api.template.update.template', [
            'type' => 'screen',
            'id' => $screenTemplate->id,
        ]);
        $params = [
            'name' => $screenTemplate->name,
            'description' => $screenTemplate->description,
            'version' => $screenTemplate->version,
            'is_public' => true,
        ];
        $response = $this->apiCall('PUT', $route, $params);

        // Check that the screen template is now shared.
        $response->assertStatus(200);
        $screenTemplate->refresh();
        $this->assertEquals(1, $screenTemplate->is_public);
    }

    public function testMakePrivateScreenTemplate()
    {
        $screenTemplate = ScreenTemplates::factory()->create(['is_public' => true]);

        $url = "/template/screen/{$screenTemplate->id}/update";
        $params = [
            'name' => $screenTemplate->name,
            'description' => $screenTemplate->description,
            'version' => $screenTemplate->version,
            'is_public' => false,
        ];
        $response = $this->apiCall('PUT', $url, $params);

        // Check that the screen template is now private.
        $response->assertStatus(200);
        $screenTemplate->refresh();
        $this->assertEquals(0, $screenTemplate->is_public);
    }

    public function testShowScreenTemplate()
    {
        // Create screen template
        $name = 'Test Screen Template';
        $screenTemplate = ScreenTemplates::factory()->create(
            [
                'name' => $name,
                'description' => 'Test Screen Template Description',
            ]);

        $route = route('api.screenBuilder.template.show', ['screen', $screenTemplate->id]);
        $response = $this->apiCall('GET', $route);
        $response->assertStatus(200);

        // Assert that our database has the screen template and the editing screen for that screen we created
        $editingScreen = Screen::where('id', $response->json()['id'])->firstOrFail();
        $screenTemplate = ScreenTemplates::where('editing_screen_uuid', $editingScreen->uuid)->firstOrFail();

        $this->assertEquals($editingScreen->title, $screenTemplate->name);
        $this->assertEquals($editingScreen->description, $screenTemplate->description);
        $this->assertEquals(1, $editingScreen->is_template);
        $this->assertEquals('SCREEN_TEMPLATE', $editingScreen->asset_type);
    }

    public function testImportExportScreenTemplate()
    {
        $adminUser = User::factory()->create();
        $screenTemplate = ScreenTemplates::factory()->create(['name' => 'ScreenTemplate', 'user_id' => $adminUser->id]);
        $payload = $this->export($screenTemplate, ScreenTemplatesExporter::class);
        $screenTemplate->delete();
        $this->assertDatabaseMissing('screen_templates', ['name' => $screenTemplate->name]);

        // Import Screen Template
        $actingAsUser = User::factory()->create();
        $this->actingAs($actingAsUser)->import($payload);
        $this->assertDatabaseHas('screen_templates', ['name' => $screenTemplate->name]);
        $importedTemplate = ScreenTemplates::where('name', $screenTemplate->name)->first();
        $this->assertEquals($actingAsUser->id, $importedTemplate->user_id);
    }

    public function testImportExportScreenTemplatesRoutes()
    {
        $screenTemplate = ScreenTemplates::factory()->create(['is_public' => true, 'name' => 'Screen Template Routes']);
        // Test download route
        $route = route('api.export.download', ['screen-template', $screenTemplate->id]);
        $response = $this->apiCall('POST', $route);
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=screen_template_routes.json');
        // Test import route
        $payload = $this->export($screenTemplate, ScreenTemplatesExporter::class);
        $jsonFileName = 'screen_template_routes.json';
        $file = UploadedFile::fake()->createWithContent($jsonFileName, json_encode($payload));
        // API call to import screen template
        $url = '/template/screen/do-import';
        $params = ['file' => $file];
        $importResponse = $this->apiCall('POST', $url, $params);
        $importResponse->assertStatus(200);
        $this->assertDatabaseHas('screen_templates', ['name' => $screenTemplate->name . ' 2']);
        $this->get('/screen-template/import')->assertStatus(200)->assertSee('Import Screen Template');
    }

    public function testSharedTemplateAuthorization()
    {
        $user = User::factory()->create();
        $sharedTemplate = ScreenTemplates::factory()->shared()->create(['user_id' => $user->id]);

        // Owner of the template should be able to configure the template.
        $route = route('templates.configure', [
            'type' => 'screen',
            'template' => $sharedTemplate->id,
        ]);
        $response = $this->actingAs($user, 'web')->call('GET', $route);
        $response->assertStatus(200);

        // Non-owner should not be able to configure the template.
        $nonOwner = User::factory()->create();
        $response = $this->actingAs($nonOwner, 'web')->call('GET', $route);
        $response->assertStatus(403);

        // Non-owner should not be able to delete the template.
        $route = route('api.template.delete', [
            'type' => 'screen',
            'id' => $sharedTemplate->id,
        ]);
        $response = $this->actingAs($nonOwner, 'api')->call('DELETE', $route);
        $response->assertStatus(403);

        // Owner of the template should be able to delete the template.
        $response = $this->actingAs($user, 'api')->call('DELETE', $route);
        $response->assertStatus(200);
    }
}
