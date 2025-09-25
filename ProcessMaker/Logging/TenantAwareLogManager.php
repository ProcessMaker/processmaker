<?php

namespace ProcessMaker\Logging;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;

class TenantAwareLogManager extends LogManager
{
    /**
     * Get a log channel instance.
     *
     * @param  string|null  $channel
     * @return \Psr\Log\LoggerInterface
     */
    public function channel($channel = null)
    {
        $channel = $channel ?: $this->getDefaultDriver();
        
        // If we're using the default channel and have tenant context, use tenant channel
        if ($channel === $this->getDefaultDriver() && Context::get('tenantId')) {
            return $this->get('tenant');
        }
        
        return parent::channel($channel);
    }
    
    /**
     * Get the default log driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['logging.default'];
    }
    
    /**
     * Create a custom log channel instance.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createCustomDriver(array $config)
    {
        $driver = $config['driver'] ?? null;
        
        if ($driver === 'tenant-aware') {
            return $this->createTenantAwareDriver($config);
        }
        
        return parent::createCustomDriver($config);
    }
    
    /**
     * Create a tenant-aware driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createTenantAwareDriver(array $config)
    {
        $tenantId = Context::get('tenantId') ?? 'no-tenant';
        
        // Create tenant-specific log path (use base_path to avoid tenant storage path issues)
        $logPath = base_path("storage/logs/tenants/tenant_{$tenantId}.log");
        
        // Ensure the directory exists
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Create the logger with tenant-specific configuration
        return $this->app->make('log')->build([
            'driver' => 'daily',
            'path' => $logPath,
            'level' => $config['level'] ?? 'debug',
            'days' => $config['days'] ?? 7,
            'processors' => [
                \Monolog\Processor\PsrLogMessageProcessor::class,
                \ProcessMaker\Logging\TenantContextProcessor::class,
            ],
        ]);
    }
}
