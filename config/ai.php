<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Microservice Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for AI microservice integration.
    |
    */
    'rag_collections' => [
        'enabled' => env('AI_RAG_COLLECTIONS_ENABLED', false),
    ],
    'genie_client' => [
        'timeout' => (int) env('AI_GENIE_CLIENT_TIMEOUT', env('API_TIMEOUT', 60000)),
    ],
];
