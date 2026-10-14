<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Migration;

use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use ProcessMaker\Mcp\Migration\ListAllTools;
use ProcessMaker\Mcp\Migration\WriterServer;
use Tests\TestCase;

class WriterServerTest extends TestCase
{
    public function testListToolsReturnsFullCatalogWhenClientRequestsPerPage15(): void
    {
        $server = new WriterServer(new FakeTransporter());
        $context = $server->createContext();
        $request = new JsonRpcRequest(1, 'tools/list', ['per_page' => 15]);

        $response = (new ListAllTools())->handle($request, $context);
        $payload = $response->toArray();

        $tools = $payload['result']['tools'];
        $names = array_column($tools, 'name');

        $this->assertGreaterThanOrEqual(30, count($tools));
        $this->assertArrayNotHasKey('nextCursor', $payload['result']);
        $this->assertContains('apply_migration_bundle', $names);
        $this->assertContains('validate_process', $names);
        $this->assertContains('link_screen_to_task', $names);
    }
}
