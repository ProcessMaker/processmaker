<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Script Runners
    |--------------------------------------------------------------------------
    |
    | This option controls the available Script Runners.
    |
    */
    'javascript' => [
        'name' => 'JavaScript',
        'runner' => 'NodeRunner',
        'mime_type' => 'text/javascript',
        'image' => env('SCRIPTS_NODE_IMAGE', 'processmaker4/executor-node'),
        'options' => ['gitRepoId' => 'sdk-node']
    ],
];
