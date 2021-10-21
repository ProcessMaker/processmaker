<?php

namespace ProcessMaker\Bpmn;

use Mustache_LambdaHelper;
use Illuminate\Support\Facades\Hash;

class MustacheOptions
{
    public $helpers = [];

    public function __construct()
    {
        $this->helpers = [
            'base64' => [$this, 'base64'],
            'html64' => [$this, 'html64'],
            'key' => [$this, 'key'],
            'json' => [$this, 'json'],
            'serialize' => [$this, 'serialize'],
            'xml' => [$this, 'xml'],
        ];
    }
    
    public function html64($text, Mustache_LambdaHelper $helper)
    {
        return base64_encode('<html><body>' . $helper->render($text) . '</body></html>');
    }

    public function base64($text, Mustache_LambdaHelper $helper)
    {
        return base64_encode($helper->render($text));
    }

    public function key($text, Mustache_LambdaHelper $helper)
    {
        return urlencode(Hash::make($helper->render($text)));
    }
    
    public function json($text)
    {
        return json_encode($text);
    }
    
    public function serialize($text)
    {
        return serialize($text);
    }
    
    public function xml($text)
    {
        return xmlrpc_encode($text);
    }
}
