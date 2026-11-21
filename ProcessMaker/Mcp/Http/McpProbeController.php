<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GET /mcp/* is not part of Streamable HTTP (POST only), but Cursor and
 * browsers often probe the configured URL. Returning 405 hides OAuth;
 * return 401 + WWW-Authenticate so clients can discover auth instead.
 */
class McpProbeController
{
    public function __invoke(Request $request): Response|JsonResponse
    {
        $path = trim($request->path(), '/');
        $metadata = $request->getSchemeAndHttpHost()
            . '/.well-known/oauth-protected-resource/'
            . $path;
        $wwwAuthenticate = 'Bearer realm="mcp", resource_metadata="' . $metadata . '"';

        $wantsBrowserPage = $request->acceptsHtml()
            && !$request->expectsJson()
            && !str_contains((string) $request->header('Accept'), 'text/event-stream')
            && !str_contains((string) $request->header('Accept'), 'application/json');

        if ($wantsBrowserPage) {
            $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ProcessMaker MCP</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; padding: 0 1rem; line-height: 1.5;">
  <h1>ProcessMaker MCP</h1>
  <p>This URL is an <strong>MCP API endpoint</strong> (JSON-RPC over <code>POST</code>). Opening it in a browser will not complete authorization.</p>
  <p>In Cursor, use <strong>Settings → MCP → Connect</strong> on the <code>processmaker</code> server. Cursor should open <code>/oauth/authorize</code>, not this page.</p>
  <p>OAuth discovery: <a href="/.well-known/oauth-authorization-server">/.well-known/oauth-authorization-server</a></p>
</body>
</html>
HTML;

            return response($html, 401, [
                'WWW-Authenticate' => $wwwAuthenticate,
                'Allow' => 'POST',
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response()
            ->json([
                'error' => 'Unauthorized',
                'hint' => 'Use POST with a Bearer token. Discover OAuth via the WWW-Authenticate header.',
            ], 401)
            ->header('WWW-Authenticate', $wwwAuthenticate)
            ->header('Allow', 'POST');
    }
}
