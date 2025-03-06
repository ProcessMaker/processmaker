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
];
