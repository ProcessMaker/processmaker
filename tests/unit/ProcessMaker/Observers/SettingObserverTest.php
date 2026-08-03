<?php

namespace Tests\Unit\ProcessMaker\Observers;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Models\Setting;
use ProcessMaker\Observers\SettingObserver;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SettingObserverTest extends TestCase
{
    public function test_it_schedules_one_refresh_callback_per_scope(): void
    {
        $this->app->forgetScopedInstances();

        $observer = app(SettingObserver::class);
        $callbackCount = $this->terminatingCallbackCount();

        $observer->saving($this->setting('first-setting'));
        $observer->saving($this->setting('second-setting'));
        $observer->deleted($this->setting('third-setting'));

        $this->assertSame($observer, app(SettingObserver::class));
        $this->assertSame($callbackCount + 1, $this->terminatingCallbackCount());
    }

    public function test_octane_termination_allows_a_refresh_callback_in_the_next_scope(): void
    {
        $this->app->forgetScopedInstances();

        $firstObserver = app(SettingObserver::class);
        $firstObserver->saving($this->setting('first-request-setting'));
        $callbackCount = $this->terminatingCallbackCount();

        (new FlushTemporaryContainerInstances())->handle(new RequestTerminated(
            $this->app,
            $this->app,
            Request::create('/first-request'),
            new Response()
        ));

        $secondObserver = app(SettingObserver::class);
        $secondObserver->saving($this->setting('second-request-setting'));

        $this->assertNotSame($firstObserver, $secondObserver);
        $this->assertSame($callbackCount + 1, $this->terminatingCallbackCount());
    }

    public function test_eloquent_saves_updates_and_deletes_share_the_scoped_observer(): void
    {
        $this->app->forgetScopedInstances();
        $callbackCount = $this->terminatingCallbackCount();

        $firstSetting = Setting::factory()->create([
            'key' => 'four-32505-first-setting',
            'config' => 'first value',
            'format' => 'text',
        ]);
        Setting::factory()->create([
            'key' => 'four-32505-second-setting',
            'config' => 'second value',
            'format' => 'text',
        ]);

        $firstSetting->config = 'updated value';
        $firstSetting->save();
        $firstSetting->delete();

        $this->assertSame($callbackCount + 1, $this->terminatingCallbackCount());
    }

    public function test_it_invalidates_every_setting_cache_entry_while_debouncing_the_refresh(): void
    {
        $this->app->forgetScopedInstances();

        $observer = app(SettingObserver::class);
        $settingCache = SettingCacheFactory::getSettingsCache();
        $settings = [
            $this->setting('cached-first-setting'),
            $this->setting('cached-second-setting'),
            $this->setting('cached-deleted-setting'),
        ];

        foreach ($settings as $setting) {
            $settingCache->set($settingCache->createKey(['key' => $setting->key]), 'cached value');
        }

        $callbackCount = $this->terminatingCallbackCount();
        $observer->saving($settings[0]);
        $observer->saving($settings[1]);
        $observer->deleted($settings[2]);

        foreach ($settings as $setting) {
            $this->assertTrue($settingCache->missing(
                $settingCache->createKey(['key' => $setting->key])
            ));
        }

        $this->assertSame($callbackCount + 1, $this->terminatingCallbackCount());
    }

    public function test_the_terminating_callback_dispatches_the_refresh_job_synchronously(): void
    {
        Bus::fake([RefreshArtisanCaches::class]);
        $this->app->forgetScopedInstances();

        $callbackCount = $this->terminatingCallbackCount();
        app(SettingObserver::class)->saving($this->setting('refresh-job-setting'));

        Bus::assertNotDispatched(RefreshArtisanCaches::class);

        $callbacks = $this->terminatingCallbacks();
        $this->app->call($callbacks[$callbackCount]);

        Bus::assertDispatchedSyncTimes(RefreshArtisanCaches::class, 1);
    }

    private function setting(string $key): Setting
    {
        return new Setting([
            'key' => $key,
            'config' => 'value',
            'format' => 'text',
        ]);
    }

    private function terminatingCallbackCount(): int
    {
        return count($this->terminatingCallbacks());
    }

    private function terminatingCallbacks(): array
    {
        return (new ReflectionProperty(Application::class, 'terminatingCallbacks'))
            ->getValue($this->app);
    }
}
