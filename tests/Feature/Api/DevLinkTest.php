<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use ProcessMaker\Http\Controllers\Api\DevLinkController;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleInstance;
use ProcessMaker\Models\BundleSetting;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    use RequestHelper;

    public function testShowBundle()
    {
        $bundle = Bundle::factory()->create();
        $response = $this->apiCall('GET', route('api.devlink.local-bundle', ['bundle' => $bundle->id]));

        $response->assertStatus(200);
        $this->assertEquals($bundle->id, $response->json()['id']);
    }

    public function testCreateBundleInitializesPublishedFingerprint()
    {
        $response = $this->apiCall('POST', route('api.devlink.create-bundle'), [
            'name' => 'Versioned Bundle',
            'published' => true,
        ]);

        $response->assertCreated();
        $this->assertStringStartsWith('v1:', $response->json('published_fingerprint'));
        $this->assertSame(1, $response->json('version'));
    }

    public function testPublishNewVersionRejectsUnchangedBundleWithoutNotification()
    {
        $createResponse = $this->apiCall('POST', route('api.devlink.create-bundle'), [
            'name' => 'Unchanged Bundle',
            'published' => true,
        ]);
        $bundle = Bundle::findOrFail($createResponse->json('id'));
        $originalFingerprint = $bundle->published_fingerprint;
        BundleInstance::create([
            'bundle_id' => $bundle->id,
            'instance_url' => 'https://target.test/bundle-updated',
        ]);
        Http::fake();

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error.message', 'There are no changes to publish for this bundle.');
        $bundle->refresh();
        $this->assertSame(1, (int) $bundle->version);
        $this->assertSame($originalFingerprint, $bundle->published_fingerprint);
        Http::assertNothingSent();
    }

    public function testPublishNewVersionIncrementsOnceAfterAssetChange()
    {
        $createResponse = $this->apiCall('POST', route('api.devlink.create-bundle'), [
            'name' => 'Changed Bundle',
            'published' => true,
        ]);
        $bundle = Bundle::findOrFail($createResponse->json('id'));
        $originalFingerprint = $bundle->published_fingerprint;
        $bundle->addAsset(Screen::factory()->create(['title' => 'New Screen']));
        BundleInstance::create([
            'bundle_id' => $bundle->id,
            'instance_url' => 'https://target.test/bundle-updated',
        ]);
        Http::fake([
            'https://target.test/bundle-updated' => Http::response([], 200),
        ]);

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(200);
        $bundle->refresh();
        $this->assertSame(2, (int) $bundle->version);
        $this->assertNotSame($originalFingerprint, $bundle->published_fingerprint);
        Http::assertSentCount(1);

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(422);
        $this->assertSame(2, (int) $bundle->refresh()->version);
        Http::assertSentCount(1);

        $bundle->assets()->firstOrFail()->delete();
        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(200);
        $this->assertSame(3, (int) $bundle->refresh()->version);
        Http::assertSentCount(2);
    }

    public function testPublishNewVersionEstablishesLegacyFingerprint()
    {
        $bundle = Bundle::factory()->create([
            'published_fingerprint' => null,
            'version' => 4,
        ]);

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(200);
        $bundle->refresh();
        $this->assertSame(5, (int) $bundle->version);
        $this->assertStringStartsWith('v1:', $bundle->published_fingerprint);

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error.message', 'There are no changes to publish for this bundle.');
        $this->assertSame(5, (int) $bundle->refresh()->version);
    }

    public function testPublishNewVersionSupportsObjectJsonSettingConfiguration()
    {
        $createResponse = $this->apiCall('POST', route('api.devlink.create-bundle'), [
            'name' => 'Bundle With Settings',
            'published' => true,
        ]);
        $bundle = Bundle::findOrFail($createResponse->json('id'));
        $user = User::factory()->create();
        $setting = BundleSetting::create([
            'bundle_id' => $bundle->id,
            'setting' => 'users',
            'config' => ['id' => [$user->id]],
        ]);
        $this->assertIsArray($setting->fresh()->config);

        BundleInstance::create([
            'bundle_id' => $bundle->id,
            'instance_url' => 'https://target.test/bundle-updated',
        ]);
        Http::fake([
            'https://target.test/bundle-updated' => Http::response([], 200),
        ]);

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(200);
        $this->assertSame(2, (int) $bundle->refresh()->version);
        Http::assertSentCount(1);

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error.message', 'There are no changes to publish for this bundle.');
        $this->assertSame(2, (int) $bundle->refresh()->version);
        Http::assertSentCount(1);
    }

    public function testPublishNewVersionRejectsRemoteBundle()
    {
        $devLink = DevLink::factory()->create();
        $bundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
        ]);
        Http::fake();

        $response = $this->apiCall(
            'POST',
            route('api.devlink.increase-bundle-version', ['bundle' => $bundle->id]),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error.message', 'Bundle is not editable');
        Http::assertNothingSent();
    }

    public function testAddAssets()
    {
        $screen1 = Screen::factory()->create();
        $screen2 = Screen::factory()->create();

        $bundle = Bundle::factory()->create();

        $bundle->addAsset($screen1);
        $bundle->addAsset($screen2);

        $response = $this->apiCall('POST', route('api.devlink.add-asset', ['bundle' => $bundle->id]), [
            'id' => $screen2->id,
            'type' => $screen2::class,
        ]);

        // assert an error is returned about screen2 already being in the bundle
        $response->assertStatus(422);
        $this->assertEquals('Asset already exists in bundle', $response->json()['error']['message']);
    }

    public function testInstallRemoteAsset()
    {
        $screen = Screen::factory()->create();
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
        ]);

        Http::fake([
            'remote-instance.test/*' => function ($request) use ($screen) {
                $httpRequest = new \Illuminate\Http\Request();
                $httpRequest->replace($request->data());

                $response = (new DevLinkController)->exportLocalAsset($httpRequest);

                // Modify to ensure it gets installed, since we are on the same instance
                $response['export'][$screen->uuid]['attributes']['title'] = 'Modified title';

                return Http::response($response, 200);
            },
        ]);

        $response = $this->apiCall(
            'POST',
            route(
                'api.devlink.install-remote-asset',
                ['devLink' => $devLink->id],
            ),
            ['id' => $screen->id, 'class' => $screen::class]
        );

        $this->assertEquals('queued', $response->json()['status']);

        $screen->refresh();
        $this->assertEquals('Modified title', $screen->title);
    }
}
