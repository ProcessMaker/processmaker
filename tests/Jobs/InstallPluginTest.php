<?php

namespace Tests\Jobs;

use Illuminate\Support\Facades\Event;
use Mockery;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Jobs\InstallPlugin;
use ProcessMaker\Managers\PluginManager;
use Tests\TestCase;

class InstallPluginTest extends TestCase
{
    public function testHandleInstallsFromUrl(): void
    {
        Event::fake([PluginLog::class]);

        $manager = Mockery::mock(PluginManager::class);
        $manager->shouldReceive('install')
            ->once()
            ->with(
                'https://github.com/ProcessMaker/package-translations.git',
                'develop',
                null,
                7,
                null,
                null,
            );

        $job = new InstallPlugin(
            userId: 7,
            url: 'https://github.com/ProcessMaker/package-translations.git',
            branch: 'develop',
        );
        $job->handle($manager);

        Event::assertDispatched(PluginLog::class, function (PluginLog $event) {
            return $event->status === 'starting'
                && $event->userId === 7;
        });
    }

    public function testHandleInstallsFromZip(): void
    {
        Event::fake([PluginLog::class]);

        $zipPath = storage_path('app/plugin-uploads/example.zip');
        $manager = Mockery::mock(PluginManager::class);
        $manager->shouldReceive('installFromZip')
            ->once()
            ->with($zipPath, 7);

        $job = new InstallPlugin(
            userId: 7,
            zipPath: $zipPath,
        );
        $job->handle($manager);

        Event::assertDispatched(PluginLog::class, function (PluginLog $event) {
            return $event->status === 'starting';
        });
    }

    public function testHandleRevertsAndRethrowsWhenInstallFails(): void
    {
        Event::fake([PluginLog::class]);

        $manager = Mockery::mock(PluginManager::class);
        $manager->shouldReceive('install')
            ->once()
            ->andThrow(new \RuntimeException('Git clone exceeded the timeout while installing package-plg.'));
        $manager->shouldReceive('revertFailedInstall')
            ->once()
            ->with(7);

        $job = new InstallPlugin(
            userId: 7,
            url: 'https://github.com/ProcessMaker/package-plg.git',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeded the timeout');

        $job->handle($manager);
    }

    public function testFailedBroadcastsError(): void
    {
        Event::fake([PluginLog::class]);

        $job = new InstallPlugin(
            userId: 7,
            url: 'https://github.com/ProcessMaker/package-translations.git',
        );
        $job->failed(new \RuntimeException(
            "The process \"'git' 'clone' 'https://token:secret@github.com/ProcessMaker/package-plg'\" exceeded the timeout of 300 seconds."
        ));

        Event::assertDispatched(PluginLog::class, function (PluginLog $event) {
            return $event->status === 'error'
                && str_contains($event->message, 'exceeded the timeout')
                && !str_contains($event->message, 'secret')
                && str_contains($event->message, 'https://github.com/ProcessMaker/package-plg')
                && $event->userId === 7;
        });
    }
}
