<?php

namespace ProcessMaker\Logging;

use Illuminate\Support\Facades\Context;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Formatter\LineFormatter;

class TenantAwareHandler extends AbstractProcessingHandler
{
    private string $baseFilename;
    private int $maxFiles;
    private array $handlers = [];
    
    /**
     * Create a new tenant-aware handler instance.
     *
     * @param string $filename
     * @param int $maxFiles
     * @param string $level
     * @param bool $bubble
     */
    public function __construct(
        string $filename,
        int $maxFiles = 7,
        $level = 'DEBUG',
        bool $bubble = true
    ) {
        $this->baseFilename = $filename;
        $this->maxFiles = $maxFiles;
        
        parent::__construct($level, $bubble);
        
        // Set default formatter
        $this->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        ));
    }
    
    /**
     * Handle a log record.
     *
     * @param LogRecord $record
     * @return bool
     */
    public function handle(LogRecord $record): bool
    {
        // Get current tenant ID from context
        $tenantId = Context::get('tenantId') ?? 'no-tenant';

        // Get or create handler for this tenant
        $handler = $this->getHandlerForTenant($tenantId);
        
        // Remove context from the log record (e.g. {"tenantId":1})
        $record->extra = [];

        return $handler->handle($record);
    }
    
    /**
     * Get handler for specific tenant.
     *
     * @param string $tenantId
     * @return \Monolog\Handler\RotatingFileHandler
     */
    private function getHandlerForTenant(string $tenantId): \Monolog\Handler\RotatingFileHandler
    {
        if (!isset($this->handlers[$tenantId])) {
            $filename = $this->getTenantFilename($tenantId);
            
            $this->handlers[$tenantId] = new \Monolog\Handler\RotatingFileHandler(
                $filename,
                $this->maxFiles,
                $this->level,
                $this->bubble
            );
            
            $this->handlers[$tenantId]->setFormatter($this->getFormatter());
        }
        
        return $this->handlers[$tenantId];
    }
    
    /**
     * Get tenant-specific filename.
     *
     * @param string $tenantId
     * @return string
     */
    private function getTenantFilename(string $tenantId): string
    {
        $pathInfo = pathinfo($this->baseFilename);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        // Define the log directory
        $logDir = $directory;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // If tenant ID is no-tenant, return the base filename
        if ($tenantId === 'no-tenant') {
            return $logDir . '/' . $filename . $extension;
        }
        // Create tenant-specific filename
        return $logDir . '/' . $filename . '_tenant_' . $tenantId . $extension;
    }
    
    /**
     * Write a log record.
     *
     * @param LogRecord $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        // This method is not used since we override handle()
    }
}
