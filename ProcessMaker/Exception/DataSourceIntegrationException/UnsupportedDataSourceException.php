<?php

namespace ProcessMaker\Exception\DataSourceIntegrationException;

use Exception;

class UnsupportedDataSourceException extends DataSourceIntegrationException
{
    public function __construct(string $source, int $code = 0, ?Exception $previous = null)
    {
        $message = "Unsupported data source integration: '{$source}'";
        parent::__construct($message, $code, $previous);
    }
}
