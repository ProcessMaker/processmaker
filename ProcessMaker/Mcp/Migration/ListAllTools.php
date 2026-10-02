<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\Pagination\CursorPaginator;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

/**
 * Cursor and other MCP clients often request tools/list with per_page=15
 * and do not follow nextCursor. Return the full tool catalog in one response.
 */
class ListAllTools implements Method
{
    public function handle(JsonRpcRequest $request, ServerContext $context): JsonRpcResponse
    {
        $tools = $context->tools();
        $perPage = max($tools->count(), $context->perPage($request->get('per_page')));

        $paginator = new CursorPaginator(
            items: $tools,
            perPage: $perPage,
            cursor: $request->cursor(),
        );

        return JsonRpcResponse::result($request->id, $paginator->paginate('tools'));
    }
}
