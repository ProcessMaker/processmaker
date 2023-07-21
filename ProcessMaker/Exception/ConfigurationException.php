<?php

namespace ProcessMaker\Exception;

use Exception;
use ProcessMaker\Models\ProcessRequestToken;

class ConfigurationException extends Exception
{
    public function __construct($message)
    {
        if (config('app.configuration_debug_mode')) {
            throw new Exception($message);
        } else {
            parent::__construct($message);
        }
    }

    public function getMessageForData(ProcessRequestToken $token)
    {
        $message = $token->element_name . ' (' . $token->element_id . '): ' . $this->getMessage();

        return [
            '_configuration_error_' . $token->element_id => $message,
        ];
    }
}
