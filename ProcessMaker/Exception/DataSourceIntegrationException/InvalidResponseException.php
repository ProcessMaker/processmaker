<?php

namespace ProcessMaker\Exception\DataSourceIntegrationException;

use Exception;

/**
 * Thrown when parsing API responses fails
 */
class InvalidResponseException extends DataSourceIntegrationException
{
    public function __construct(string $source, string $error = '', int $code = 0, ?Exception $previous = null)
    {
        $message = "Invalid API response from '{$source}'";
        if ($error) {
            $message .= " - {$error}";
        }
        parent::__construct($message, $code, $previous);
    }
}
