<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            if ($this->app->isLocal()) {
                return true;
            }

            return $entry->isReportableException() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });

        Telescope::tag(function (IncomingEntry $entry) {
            if ($opcache = \function_exists('opcache_get_configuration')) {
                if ($opcache = \opcache_get_configuration()) {
                    $opcache = $opcache['directives']['opcache.enable'];
                }
            }

            return [
                // PHP process ID
                'Pid::' . getmypid(),

                // PHP OPCache enabled
                $opcache ? 'OPCache::Enabled' : 'OPCache::Disabled',

                // Laravel app config cache
                $this->app->configurationIsCached() ? 'Configuration::Cached' : 'Configuration::NotCached',

                // Laravel routes cached
                $this->app->routesAreCached() ? 'Routes::Cached' : 'Routes::NotCached',

                // Laravel events caches
                $this->app->eventsAreCached() ? 'Events::Cached' : 'Events::NotCached',
            ];
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->isLocal()) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
