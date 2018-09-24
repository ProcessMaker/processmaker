<?php

die("HEREERERER");

namespace App\Facades;

use Illuminate\Support\Facades\Response as BaseResponse;

class Response extends BaseResponse {

    public function __construct($content)
    {
        die("HERE");
    }

    protected static function getFacadeAccessor() { die("HERE2"); return 'some.service'; }

}