<?php

use Laravel\Mcp\Facades\Mcp;
use ProcessMaker\Mcp\Servers\ProcessServer;
use ProcessMaker\Mcp\Servers\ScreenServer;

/*
|--------------------------------------------------------------------------
| MCP servers
|--------------------------------------------------------------------------
|
| HTTP: POST /mcp/screens or /mcp/processes with a ProcessMaker Passport bearer token.
| Local: php artisan mcp:start screens|processes
|
*/

Mcp::web('/mcp/screens', ScreenServer::class)
    ->middleware(['auth:api']);

Mcp::local('screens', ScreenServer::class);

Mcp::web('/mcp/processes', ProcessServer::class)
    ->middleware(['auth:api']);

Mcp::local('processes', ProcessServer::class);
