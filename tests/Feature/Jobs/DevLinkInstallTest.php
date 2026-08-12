<?php

namespace Tests\Feature\Jobs;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ProcessMaker\Exception\DevLinkRemoteBundleException;
use ProcessMaker\Exception\DevLinkRemoteValidationException;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Events\ImportLog;
use ProcessMaker\Jobs\DevLinkInstall;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\Bundle;
use RuntimeException;
use Tests\TestCase;

class DevLinkInstallTest extends TestCase
{
    private const MISSING_DISPLAY_SCREEN_MESSAGE = 'The dashboard "DevLink Dashboard" references a Display Screen that is no longer available. Assign a valid Display Screen on the source instance and try again.';

    private const REMOTE_ERROR_MESSAGE = 'The remote instance could not complete the DevLink request. Check the source instance logs and try again.';

    private const UNEXPECTED_ERROR_MESSAGE = 'The DevLink operation could not be completed. Check the target instance logs and try again.';

    public function testFailedUnexpectedJobSanitizesErrorAndLogsCorrelatedException()
    {
        Event::fake([ImportLog::class]);
        Log::spy();
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

        $exception = new RuntimeException('Sensitive installation details');

        $job->failed($exception);

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->type === 'error'
                && $event->message === self::UNEXPECTED_ERROR_MESSAGE
                && !str_contains($event->message, RuntimeException::class)
                && !str_contains($event->message, 'Sensitive installation details')
                && $event->operationId === 'operation-123';
        });

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($exception) {
                return $message === 'DevLink operation failed.'
                    && $context['exception'] === $exception
                    && $context['operation_id'] === 'operation-123'
                    && $context['dev_link_id'] === 456
                    && $context['type'] === DevLinkInstall::TYPE_INSTALL_BUNDLE;
            });
    }

    public function testFailedRemoteRequestSanitizesHttpResponseBody()
    {
        Event::fake([ImportLog::class]);
        Log::spy();
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
            'operation-remote-error',
        );
        $exception = new RequestException(new Response(new PsrResponse(
            500,
            [],
            json_encode([
                'message' => 'The MAC is invalid.',
                'exception' => 'Illuminate\\Contracts\\Encryption\\DecryptException',
                'trace' => ['sensitive trace'],
            ])
        )));

        $job->failed($exception);

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->type === 'error'
                && $event->message === self::REMOTE_ERROR_MESSAGE
                && !str_contains($event->message, '500')
                && !str_contains($event->message, 'MAC')
                && !str_contains($event->message, RequestException::class)
                && $event->operationId === 'operation-remote-error';
        });

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($exception) {
                return $message === 'DevLink operation failed.'
                    && $context['exception'] === $exception
                    && $context['operation_id'] === 'operation-remote-error'
                    && $context['dev_link_id'] === 456
                    && $context['type'] === DevLinkInstall::TYPE_INSTALL_BUNDLE;
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

    public function testFailedRemoteValidationJobIncludesOperationIdInActionableErrorEvent()
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
            'operation-remote-validation',
        );

        $job->failed(new DevLinkRemoteValidationException(self::MISSING_DISPLAY_SCREEN_MESSAGE));

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->type === 'error'
                && $event->message === self::MISSING_DISPLAY_SCREEN_MESSAGE
                && $event->operationId === 'operation-remote-validation';
        });
    }

    public function testFailedLocalValidationJobIncludesOperationIdInActionableErrorEvent()
    {
        Event::fake([ImportLog::class]);
        Storage::fake('local');
        $exception = ValidationException::withMessages([
            'dependencies' => [self::MISSING_DISPLAY_SCREEN_MESSAGE],
        ]);

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
            DevLinkInstall::TYPE_REINSTALL_BUNDLE,
            'operation-local-validation',
        );

        $job->failed($exception);

        Event::assertDispatched(ImportLog::class, function (ImportLog $event) {
            return $event->type === 'error'
                && $event->message === self::MISSING_DISPLAY_SCREEN_MESSAGE
                && $event->operationId === 'operation-local-validation';
        });
    }
}
