<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ProcessMaker\Http\Controllers\Api\DevLinkController;
use ProcessMaker\Jobs\DevLinkInstall;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
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

    public function testInstallEndpointsPassOperationIdToQueuedJobs()
    {
        Bus::fake();

        $operationId = (string) Str::uuid();
        $devLink = DevLink::factory()->create();
        $bundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
        ]);

        $installResponse = $this->apiCall(
            'POST',
            route('api.devlink.install-remote-bundle', [
                'devLink' => $devLink->id,
                'remoteBundleId' => 123,
            ]),
            ['operation_id' => $operationId]
        );
        $reinstallResponse = $this->apiCall(
            'POST',
            route('api.devlink.reinstall-bundle', ['bundle' => $bundle->id]),
            ['operation_id' => $operationId]
        );
        $assetResponse = $this->apiCall(
            'POST',
            route('api.devlink.install-remote-asset', ['devLink' => $devLink->id]),
            [
                'id' => 456,
                'class' => Screen::class,
                'operation_id' => $operationId,
            ]
        );

        $installResponse->assertOk()->assertExactJson(['status' => 'queued']);
        $reinstallResponse->assertOk()->assertExactJson(['status' => 'queued']);
        $assetResponse->assertOk()->assertExactJson(['status' => 'queued']);

        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) use ($operationId) {
            return $job->type === DevLinkInstall::TYPE_INSTALL_BUNDLE
                && $job->operationId === $operationId;
        });
        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) use ($operationId) {
            return $job->type === DevLinkInstall::TYPE_REINSTALL_BUNDLE
                && $job->operationId === $operationId;
        });
        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) use ($operationId) {
            return $job->type === DevLinkInstall::TYPE_IMPORT_ASSET
                && $job->operationId === $operationId;
        });
    }

    public function testInstallEndpointGeneratesOperationIdWhenMissing()
    {
        Bus::fake();

        $devLink = DevLink::factory()->create();
        $response = $this->apiCall(
            'POST',
            route('api.devlink.install-remote-asset', ['devLink' => $devLink->id]),
            [
                'id' => 456,
                'class' => Screen::class,
            ]
        );

        $response->assertOk()->assertExactJson(['status' => 'queued']);
        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) {
            return Str::isUuid($job->operationId);
        });
    }
}
