<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use Laravel\Passport\Client;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    public function testProtectedResourceMetadataIsAdvertised(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp/processes')
            ->assertOk()
            ->assertJsonPath('scopes_supported.0', 'mcp:use')
            ->assertJsonStructure([
                'resource',
                'authorization_servers',
                'scopes_supported',
            ]);
    }

    public function testAuthorizationServerMetadataIncludesRegistration(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('scopes_supported.0', 'mcp:use')
            ->assertJsonStructure([
                'issuer',
                'authorization_endpoint',
                'token_endpoint',
                'registration_endpoint',
                'code_challenge_methods_supported',
            ]);
    }

    public function testDynamicClientRegistrationCreatesPassportClient(): void
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Cursor MCP',
            'redirect_uris' => ['cursor://anysphere.cursor-mcp/oauth/callback'],
        ]);

        $response->assertOk()
            ->assertJsonPath('scope', 'mcp:use')
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonStructure([
                'client_id',
                'grant_types',
                'redirect_uris',
            ]);

        $this->assertNotNull(Client::query()->where('name', 'Cursor MCP')->first());
    }

    public function testMcpHttpUnauthorizedIncludesResourceMetadataHeader(): void
    {
        $response = $this->postJson('/mcp/processes', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [],
        ]);

        $response->assertUnauthorized();

        $wwwAuthenticate = (string) $response->headers->get('WWW-Authenticate');
        $this->assertStringContainsString('Bearer realm="mcp"', $wwwAuthenticate);
        $this->assertStringContainsString('resource_metadata=', $wwwAuthenticate);
    }

    public function testMcpGetProbeReturnsUnauthorizedWithResourceMetadata(): void
    {
        $response = $this->getJson('/mcp/processes');

        $response->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized');

        $wwwAuthenticate = (string) $response->headers->get('WWW-Authenticate');
        $this->assertStringContainsString('Bearer realm="mcp"', $wwwAuthenticate);
        $this->assertStringContainsString('oauth-protected-resource/mcp/processes', $wwwAuthenticate);
    }
}

