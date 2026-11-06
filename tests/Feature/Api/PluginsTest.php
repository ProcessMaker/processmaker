<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Bus;
use ProcessMaker\Jobs\InstallPlugin;
use ProcessMaker\Jobs\TogglePlugin;
use ProcessMaker\Jobs\UninstallPlugin;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class PluginsTest extends TestCase
{
    use RequestHelper;

    public function testInstallQueuesJobFromUrl(): void
    {
        Bus::fake([InstallPlugin::class]);

        $response = $this->apiCall('POST', '/plugins/install', [
            'url' => 'https://github.com/ProcessMaker/package-translations.git',
            'branch' => 'develop',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'queued' => true,
            'message' => 'Plugin installation queued',
        ]);

        Bus::assertDispatched(InstallPlugin::class, function (InstallPlugin $job) {
            return $job->userId === $this->user->id
                && $job->url === 'https://github.com/ProcessMaker/package-translations.git'
                && $job->branch === 'develop'
                && $job->tag === null
                && $job->zipPath === null
                && $job->timeout >= 600;
        });
    }

    public function testUninstallQueuesJob(): void
    {
        Bus::fake([UninstallPlugin::class]);

        $response = $this->apiCall('DELETE', '/plugins/package-translations');

        $response->assertStatus(200);
        $response->assertJson(['queued' => true]);

        Bus::assertDispatched(UninstallPlugin::class, function (UninstallPlugin $job) {
            return $job->pluginName === 'package-translations'
                && $job->userId === $this->user->id;
        });
    }

    public function testToggleQueuesJob(): void
    {
        Bus::fake([TogglePlugin::class]);

        $response = $this->apiCall('PATCH', '/plugins/package-translations/toggle');

        $response->assertStatus(200);
        $response->assertJson(['queued' => true]);

        Bus::assertDispatched(TogglePlugin::class, function (TogglePlugin $job) {
            return $job->pluginName === 'package-translations'
                && $job->userId === $this->user->id;
        });
    }

    public function testInstallRejectsBranchAndTagTogether(): void
    {
        Bus::fake([InstallPlugin::class]);

        $response = $this->apiCall('POST', '/plugins/install', [
            'url' => 'https://github.com/ProcessMaker/package-translations.git',
            'branch' => 'develop',
            'tag' => 'v1.0.0',
        ]);

        $response->assertStatus(422);
        Bus::assertNotDispatched(InstallPlugin::class);
    }
}
