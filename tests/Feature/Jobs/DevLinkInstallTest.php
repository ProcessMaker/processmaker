<?php

namespace Tests\Feature\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ProcessMaker\Exception\DevLinkRemoteBundleException;
use ProcessMaker\Events\ImportLog;
use ProcessMaker\Jobs\DevLinkInstall;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\Bundle;
use RuntimeException;
use Tests\TestCase;

class DevLinkInstallTest extends TestCase
{
    public function testFailedJobIncludesOperationIdInErrorEvent()
    {
        Event::fake([ImportLog::class]);
        Storage::fake('local');

        $lock = Mockery::mock();
        $lock->shouldReceive('forceRelease')->once();
        Cache::shouldReceive('lock')
            ->once()
            ->with(ImportV2::CACHE_LOCK_KEY)
            ->andReturn($lock);

        $job = new DevLinkInstall(
            123,
            456,
            Bundle::class,
            789,
            DevLinkInstall::MODE_UPDATE,
            DevLinkInstall::TYPE_INSTALL_BUNDLE,
            'operation-123',
        );

        $job->failed(new RuntimeException('Installation failed'));

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->type === 'error'
                && str_contains($event->message, 'Installation failed')
                && $event->operationId === 'operation-123';
        });
    }

    public function testFailedRemoteBundleJobIncludesOperationIdInActionableErrorEvent()
    {
        Event::fake([ImportLog::class]);
        Storage::fake('local');

        $lock = Mockery::mock();
        $lock->shouldReceive('forceRelease')->once();
        Cache::shouldReceive('lock')
            ->once()
            ->with(ImportV2::CACHE_LOCK_KEY)
            ->andReturn($lock);

        $job = new DevLinkInstall(
            123,
            456,
            Bundle::class,
            789,
            DevLinkInstall::MODE_UPDATE,
            DevLinkInstall::TYPE_INSTALL_BUNDLE,
            'operation-456',
        );
        $exception = new DevLinkRemoteBundleException([[
            'asset_type' => Bundle::class,
            'asset_id' => 789,
            'bundle_asset_id' => 321,
        ]]);

        $job->failed($exception);

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->type === 'error'
                && str_contains($event->message, 'The remote bundle contains unavailable assets')
                && !str_contains($event->message, DevLinkRemoteBundleException::class)
                && $event->operationId === 'operation-456';
        });
    }
}
