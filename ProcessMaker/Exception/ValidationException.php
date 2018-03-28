<?php

namespace ProcessMaker\Exception;

use Illuminate\Validation\ValidationException as ValidationExceptionBase;
use Illuminate\Validation\Validator;

/**
 * Description of ValidationException
 *
 */
class ValidationException extends ValidationExceptionBase
{
    const ERROR_CODE = 422;

    public function __construct(Validator $validator)
    {
        $errors = $validator->errors()->getMessages();
        foreach ($errors as $key => $messages) {
            $message = $messages[0];
            break;
        }
        $error = [
            'error'  => [
                'code'    => 422,
                'message' => $message,
            ],
            'errors' => $errors
        ];
        $response = response()->json($error, static::ERROR_CODE);
        parent::__construct($validator, $response);
    }
}
