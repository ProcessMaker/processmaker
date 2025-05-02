<?php

namespace ProcessMaker\Bpmn;

use Illuminate\Support\Facades\Hash;
use Mustache_LambdaHelper;

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

    public function html64($text, ?Mustache_LambdaHelper $helper = null)
    {
        if (!$helper) {
            // Support for PRAGMA_FILTERS
            return base64_encode('<html><body>' . $text . '</body></html>');
        }
        return base64_encode('<html><body>' . $helper->render($text) . '</body></html>');
    }

    public function base64($text, ?Mustache_LambdaHelper $helper = null)
    {
        if (!$helper) {
            // Support for PRAGMA_FILTERS
            return base64_encode($text);
        }
        return base64_encode($helper->render($text));
    }

    public function key($text, ?Mustache_LambdaHelper $helper = null)
    {
        if (!$helper) {
            // Support for PRAGMA_FILTERS
            return urlencode(Hash::make($text));
        }
        return urlencode(Hash::make($helper->render($text)));
    }

    public function json($text, ?Mustache_LambdaHelper $helper = null)
    {
        if (!$helper) {
            // Support for PRAGMA_FILTERS
            return json_encode($text);
        }
        return json_encode($helper->render($text));
    }

    public function serialize($text, ?Mustache_LambdaHelper $helper = null)
    {
        if (!$helper) {
            // Support for PRAGMA_FILTERS
            return serialize($text);
        }
        return serialize($helper->render($text));
    }

    public function xml($text, ?Mustache_LambdaHelper $helper = null)
    {
        if (!$helper) {
            // Support for PRAGMA_FILTERS
            return xmlrpc_encode($text);
        }
        return xmlrpc_encode($helper->render($text));
    }
}
