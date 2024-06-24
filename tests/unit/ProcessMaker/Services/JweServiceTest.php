<?php

namespace Tests\unit\ProcessMaker\Services;

use ProcessMaker\Services\JweService;
use Tests\TestCase;

class JweServiceTest extends TestCase
{
    public $jweService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a fake secret key for the tests.
        config(['process_intelligence.secret_key' => base64_encode(random_bytes(32))]);

        $this->jweService = new JweService();
    }

    public function testGetInstanceMetadata()
    {
        $expected = [
            'company_name' => config('process_intelligence.company_name'),
            'company_database' => config('process_intelligence.company_database'),
        ];

        $this->assertEquals($expected, $this->jweService->getInstanceMetadata());
    }

    public function testGenerateToken()
    {
        $data = [
            'user_id' => 1,
            'username' => 'john.doe',
        ];

        $token = $this->jweService->generateToken($data);
        $decodedData = $this->jweService->validateToken($token);

        $this->assertArrayHasKey('company_name', $decodedData);
        $this->assertArrayHasKey('company_database', $decodedData);
        $this->assertEquals(1, $decodedData['user_id']);
        $this->assertEquals('john.doe', $decodedData['username']);
    }

    public function testEncryptToken()
    {
        $jwt = 'test.jwt.token';
        $encrypted = $this->jweService->encrypt($jwt);

        $this->assertNotEmpty($encrypted, 'Encrypted value should not be empty');

        $decrypted = $this->jweService->decrypt($encrypted);

        $this->assertEquals($jwt, $decrypted, 'Decrypted value should match the original JWT');
    }
}
