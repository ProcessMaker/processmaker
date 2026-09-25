<?php

use Laravel\Mcp\Facades\Mcp;
use ProcessMaker\Mcp\Migration\WriterServer;

/*
| PM4 MCP — WriterServer exposes migration, design, and analysis.
| - Mcp::local  → Cursor: php artisan mcp:start {name}
| - Mcp::web    → optional HTTP: /mcp/{name}
| migration-writer is a legacy alias of processmaker-agent (same class).
*/
$mcpServers = [
    'processmaker-agent',
    'migration-writer', // alias
];

foreach ($mcpServers as $name) {
    Mcp::local($name, WriterServer::class);
    Mcp::web("/mcp/{$name}", WriterServer::class);
}
