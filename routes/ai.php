<?php

use Laravel\Mcp\Facades\Mcp;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Mcp\Http\McpProbeController;
use ProcessMaker\Mcp\Servers\DataConnectorsServer;
use ProcessMaker\Mcp\Servers\FlowGenieServer;
use ProcessMaker\Mcp\Servers\ProcessServer;
use ProcessMaker\Mcp\Servers\ScreenServer;

/*
|--------------------------------------------------------------------------
| MCP servers
|--------------------------------------------------------------------------
|
| HTTP: POST /mcp/screens, /mcp/processes, /mcp/data-connectors, or /mcp/flow-genies
| with a ProcessMaker Passport bearer token.
| Local: php artisan mcp:start screens|processes|data-connectors|flow-genies
|
*/

Mcp::web('/mcp/screens', ScreenServer::class)
    ->middleware(['auth:api']);

Mcp::local('screens', ScreenServer::class);

Mcp::web('/mcp/processes', ProcessServer::class)
    ->middleware(['auth:api']);

Mcp::local('processes', ProcessServer::class);

Mcp::web('/mcp/data-connectors', DataConnectorsServer::class)
    ->middleware(['auth:api']);

Mcp::local('data-connectors', DataConnectorsServer::class);

Mcp::web('/mcp/flow-genies', FlowGenieServer::class)
    ->middleware(['auth:api']);

Mcp::local('flow-genies', FlowGenieServer::class);

Mcp::oauthRoutes();

foreach (['screens', 'processes', 'data-connectors', 'flow-genies'] as $server) {
    Route::get('/mcp/' . $server, McpProbeController::class);
}
