<?php

return [
    /*
     * The storage config
     */
    'storage' => [
        /*
         * Returns the folder name of the chunks. The location is in storage/app/{folder_name}
         */
        'chunks' => 'chunks',
        'disk' => 'local',
    ],
    'clear' => [
        /*
         * How old chunks we should delete
         */
        'timestamp' => '-3 HOURS',
        'schedule' => [
            'enabled' => true,
            'cron' => '25 * * * *', // run every hour on the 25th minute
        ],
    ],
    'chunk' => [
        // setup for the chunk naming setup to ensure same name upload at same time
        'name' => [
            'use' => [
                // This is to deal with browser session ids, this works fine, even if using the API, we do send session ids
                'session' => true, // should the chunk name use the session id? The uploader must send cookie!,
                'browser' => false, // instead of session we can use the ip and browser?
            ],
        ],
    ],
    'handlers' => [
        // A list of handlers/providers that will be appended to existing list of handlers
        'custom' => [
        ],
        // Overrides the list of handlers - use only what you really want
        'override' => [
        ],
    ],
];
