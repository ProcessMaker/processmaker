<?php

namespace ProcessMaker\Exception\DataSourceIntegrationException;

use Exception;

/**
 * Thrown when an API request fails
 */
class ApiRequestException extends DataSourceIntegrationException
{
    public function __construct(string $source, string $endpoint, string $error = '', int $code = 0, ?Exception $previous = null)
    {
        $message = "API request failed for '{$source}' at endpoint '{$endpoint}'";
        if ($error) {
            $message .= " - {$error}";
        }
        parent::__construct($message, $code, $previous);
    }
}
