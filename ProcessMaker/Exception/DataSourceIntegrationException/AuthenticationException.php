<?php

namespace ProcessMaker\Exception\DataSourceIntegrationException;

use Exception;

/**
 * Thrown when API credentials can't be decrypted or are invalid
 */
class AuthenticationException extends DataSourceIntegrationException
{
    public function __construct(string $source, string $details = '', int $code = 0, ?Exception $previous = null)
    {
        $message = "Authentication failed for data source: '{$source}'";
        if ($details) {
            $message .= " - {$details}";
        }
        parent::__construct($message, $code, $previous);
    }
}
