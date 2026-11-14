<?php

namespace Tests\Unit\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Helpers\AbeRecipientSignature;
use ProcessMaker\Helpers\AbeResponder;
use ProcessMaker\Models\User;
use Tests\TestCase;

class AbeResponderTest extends TestCase
{
    public function testFromRequestIncludesSignedRecipientAndOptionalMetadata(): void
    {
        $tokenAbe = 'abe-token';
        $signedQuery = AbeRecipientSignature::queryString(
            $tokenAbe,
            'signed@example.com',
            7,
            [['id' => 3, 'name' => 'Reviewers']]
        );
        parse_str($signedQuery, $params);

        $request = Request::create(
            '/tasks/update_variable/' . $tokenAbe,
            'GET',
            $params,
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
            ]
        );

        Auth::logout();
        $responder = AbeResponder::fromRequest($request, $tokenAbe);

        $this->assertSame('signed@example.com', $responder['email']);
        $this->assertSame(7, $responder['user_id']);
        $this->assertSame([['id' => 3, 'name' => 'Reviewers']], $responder['groups']);
        $this->assertSame('203.0.113.10', $responder['ip_address']);
        $this->assertSame('mobile', $responder['device']);
        $this->assertArrayHasKey('acted_at', $responder);
        $this->assertArrayHasKey('user_agent', $responder);
        $this->assertArrayHasKey('email_client', $responder);
        $this->assertArrayNotHasKey('authenticated_email', $responder);
        $this->assertArrayNotHasKey('identity_mismatch', $responder);
    }

    public function testIdentityMismatchWhenAuthenticatedUserDiffersFromSignedRecipient(): void
    {
        $authUser = User::factory()->create([
            'email' => 'logged-in@example.com',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($authUser);

        $tokenAbe = 'abe-token';
        $signedQuery = AbeRecipientSignature::queryString($tokenAbe, 'signed@example.com', 9, []);
        parse_str($signedQuery, $params);
        $request = Request::create('/tasks/update_variable/' . $tokenAbe, 'GET', $params);

        $responder = AbeResponder::fromRequest($request, $tokenAbe);

        $this->assertSame('signed@example.com', $responder['email']);
        $this->assertSame('logged-in@example.com', $responder['authenticated_email']);
        $this->assertSame($authUser->id, $responder['authenticated_user_id']);
        $this->assertTrue($responder['identity_mismatch']);
    }

    public function testMatchingAuthenticatedUserSetsIdentityMismatchFalse(): void
    {
        $authUser = User::factory()->create([
            'email' => 'same@example.com',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($authUser);

        $tokenAbe = 'abe-token';
        $signedQuery = AbeRecipientSignature::queryString($tokenAbe, 'same@example.com', $authUser->id, []);
        parse_str($signedQuery, $params);
        $request = Request::create('/tasks/update_variable/' . $tokenAbe, 'GET', $params);

        $responder = AbeResponder::fromRequest($request, $tokenAbe);

        $this->assertSame('same@example.com', $responder['email']);
        $this->assertSame('same@example.com', $responder['authenticated_email']);
        $this->assertFalse($responder['identity_mismatch']);
    }

    public function testUnsignedLinkStillBuildsSparseResponder(): void
    {
        $request = Request::create('/tasks/update_variable/abe-token', 'GET', [
            'varName' => 'res',
            'varValue' => 'yes',
        ], [], [], [
            'REMOTE_ADDR' => '198.51.100.2',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0) Outlook',
        ]);

        Auth::logout();
        $responder = AbeResponder::fromRequest($request, 'abe-token');

        $this->assertArrayNotHasKey('email', $responder);
        $this->assertArrayNotHasKey('user_id', $responder);
        $this->assertSame('198.51.100.2', $responder['ip_address']);
        $this->assertSame('desktop', $responder['device']);
        $this->assertSame('Outlook', $responder['email_client']);
    }
}
