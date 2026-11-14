<?php

namespace Tests\Unit\Helpers;

use Illuminate\Http\Request;
use ProcessMaker\Helpers\AbeRecipientSignature;
use Tests\TestCase;

class AbeRecipientSignatureTest extends TestCase
{
    public function testQueryStringRoundTripsThroughVerify(): void
    {
        $tokenAbe = 'token-uuid-123';
        $groups = [
            ['id' => 2, 'name' => 'B'],
            ['id' => 1, 'name' => 'A'],
        ];

        $query = AbeRecipientSignature::queryString($tokenAbe, 'User@Example.com', 42, $groups);
        parse_str($query, $params);

        $request = Request::create('/tasks/update_variable/' . $tokenAbe, 'GET', $params);
        $verified = AbeRecipientSignature::verify($request, $tokenAbe);

        $this->assertNotNull($verified);
        $this->assertSame('user@example.com', $verified['email']);
        $this->assertSame(42, $verified['user_id']);
        $this->assertSame(
            [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']],
            $verified['groups']
        );
    }

    public function testTamperedEmailFailsVerification(): void
    {
        $tokenAbe = 'token-uuid-123';
        $query = AbeRecipientSignature::queryString($tokenAbe, 'user@example.com', 1, []);
        parse_str($query, $params);
        $params[AbeRecipientSignature::EMAIL] = 'other@example.com';

        $request = Request::create('/tasks/update_variable/' . $tokenAbe, 'GET', $params);
        $this->assertNull(AbeRecipientSignature::verify($request, $tokenAbe));
    }

    public function testMissingSignatureReturnsNull(): void
    {
        $request = Request::create('/tasks/update_variable/token-uuid', 'GET', [
            AbeRecipientSignature::EMAIL => 'user@example.com',
        ]);
        $this->assertNull(AbeRecipientSignature::verify($request, 'token-uuid'));
    }
}
