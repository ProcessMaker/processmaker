<?php

namespace Tests\Unit\ProcessMaker\Managers;

use ProcessMaker\Managers\OauthMailManager;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class OauthMailManagerTest extends TestCase
{
    private const OAUTH_FROM_ADDRESS = 'oauth-user@example.com';

    public function testGoogleAuthMethodUsesOnlyXoauth2Authenticator()
    {
        $transport = $this->createSmtpTransportForAuthMethod('google', 'oauth-access-token');

        $this->assertSame(self::OAUTH_FROM_ADDRESS, $transport->getUsername());
        $this->assertSame('oauth-access-token', $transport->getPassword());
        $this->assertAuthenticatorKeywords($transport, ['XOAUTH2']);
    }

    public function testOffice365AuthMethodUsesOnlyXoauth2Authenticator()
    {
        $transport = $this->createSmtpTransportForAuthMethod('office365', 'office-access-token');

        $this->assertSame(self::OAUTH_FROM_ADDRESS, $transport->getUsername());
        $this->assertSame('office-access-token', $transport->getPassword());
        $this->assertAuthenticatorKeywords($transport, ['XOAUTH2']);
    }

    public function testStandardAuthMethodKeepsDefaultAuthenticators()
    {
        config([
            'mail.auth_method' => 'standard',
            'mail.server_index' => 0,
            'mail.from.address' => 'standard-user@example.com',
        ]);

        $manager = new OauthMailManager($this->app);
        $transport = $this->invokeCreateSmtpTransport($manager, [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'standard-user@example.com',
            'password' => 'secret-password',
            'encryption' => 'tls',
        ]);

        $keywords = $this->getAuthenticatorKeywords($transport);

        $this->assertContains('LOGIN', $keywords);
        $this->assertContains('PLAIN', $keywords);
        $this->assertContains('XOAUTH2', $keywords);
        $this->assertGreaterThan(1, count($keywords));
        $this->assertContains(LoginAuthenticator::class, array_map('get_class', $this->getAuthenticators($transport)));
    }

    private function createSmtpTransportForAuthMethod(string $authMethod, string $accessToken): EsmtpTransport
    {
        config([
            'mail.auth_method' => $authMethod,
            'mail.server_index' => 0,
            'mail.from.address' => self::OAUTH_FROM_ADDRESS,
            'services.gmail' => [
                'key' => 'gmail-client-id',
                'secret' => 'gmail-client-secret',
                'access_token' => $accessToken,
                'refresh_token' => 'gmail-refresh-token',
                'expires_in' => time() + 3600,
            ],
            'services.office365' => [
                'tenant_id' => 'tenant-id',
                'key' => 'office-client-id',
                'secret' => 'office-client-secret',
                'access_token' => $accessToken,
                'refresh_token' => 'office-refresh-token',
                'expires_in' => now()->addHour()->format('Y-m-d H:i:s'),
            ],
        ]);

        $manager = new class($this->app, $accessToken) extends OauthMailManager {
            private string $testAccessToken;

            public function __construct($app, string $accessToken)
            {
                $this->testAccessToken = $accessToken;
                parent::__construct($app);
            }

            public function checkForExpiredAccessToken()
            {
                return $this->testAccessToken;
            }
        };

        return $this->invokeCreateSmtpTransport($manager, [
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
        ]);
    }

    private function invokeCreateSmtpTransport(OauthMailManager $manager, array $config): EsmtpTransport
    {
        $method = new ReflectionMethod(OauthMailManager::class, 'createSmtpTransport');
        $method->setAccessible(true);

        return $method->invoke($manager, $config);
    }

    private function assertAuthenticatorKeywords(EsmtpTransport $transport, array $expectedKeywords): void
    {
        $this->assertSame($expectedKeywords, $this->getAuthenticatorKeywords($transport));
        $this->assertContainsOnlyInstancesOf(XOAuth2Authenticator::class, $this->getAuthenticators($transport));
    }

    private function getAuthenticatorKeywords(EsmtpTransport $transport): array
    {
        return array_map(
            fn ($authenticator) => $authenticator->getAuthKeyword(),
            $this->getAuthenticators($transport)
        );
    }

    private function getAuthenticators(EsmtpTransport $transport): array
    {
        $property = (new ReflectionClass(EsmtpTransport::class))->getProperty('authenticators');
        $property->setAccessible(true);

        return $property->getValue($transport);
    }
}
