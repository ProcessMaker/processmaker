<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Concurrency;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Http\Controllers\Api\DevLinkController;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\Screen;
use ProcessMaker\Services\DevLink\BundleFingerprint;
use Tests\TestCase;

class DevLinkConcurrencyTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function testConcurrentPublicationAttemptsIncrementOnlyOnce()
    {
        $bundle = Bundle::factory()->create(['version' => 1]);
        $screen = Screen::factory()->create(['title' => 'Concurrent Publication Screen']);
        $bundle->published_fingerprint = app(BundleFingerprint::class)->calculate($bundle);
        $bundle->saveOrFail();
        $bundle->addAsset($screen);
        $bundleId = $bundle->id;
        $startAt = microtime(true) + 1;

        $publish = static function () use ($bundleId, $startAt) {
            while (microtime(true) < $startAt) {
                usleep(1000);
            }

            try {
                app(DevLinkController::class)->increaseBundleVersion(
                    Bundle::findOrFail($bundleId),
                    app(BundleFingerprint::class),
                );

                return 'published';
            } catch (ValidationException $exception) {
                return $exception->errors()['*'][0] ?? 'unexpected validation error';
            }
        };

        try {
            $results = Concurrency::driver('process')->run([$publish, $publish], timeout: 30);
            sort($results);

            $this->assertSame([
                'There are no changes to publish for this bundle.',
                'published',
            ], $results);
            $this->assertSame(2, (int) $bundle->refresh()->version);
        } finally {
            $bundle->assets()->delete();
            $bundle->delete();
            $screen->delete();
        }
    }
}
