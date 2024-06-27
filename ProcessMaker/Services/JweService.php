<?php

namespace ProcessMaker\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;

class JweService
{
    private $jwtConfig;

    private $jwtSecretKey;

    private $jweSecretKey;

    public function __construct()
    {
        $this->jwtSecretKey = base64_decode(config('process_intelligence.secret_key'));
        $this->jweSecretKey = base64_decode(config('process_intelligence.encryption_key'));

        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwtSecretKey)
        );
    }

    /**
     * Generates a token with the given data.
     */
    public function generateToken(array $data): string
    {
        $payload = array_merge($data, $this->getInstanceMetadata());

        $token = $this->jwtConfig->builder()
            ->withClaim('company_name', $payload['company_name'])
            ->withClaim('company_database', $payload['company_database'])
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        $jwt = $token->toString();

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $ciphertext = openssl_encrypt(
            $jwt,
            'aes-256-cbc',
            $this->jweSecretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return rtrim(strtr(base64_encode($iv . $ciphertext), '+/', '-_'), '=');
    }

    /**
     * Validates a token and extracts the data claims.
     */
    public function validateToken(string $token): array
    {
        $jwt = $this->jwtConfig->parser()->parse($token);
        assert($jwt instanceof UnencryptedToken);

        $this->jwtConfig->validator()->assert($jwt, ...$this->jwtConfig->validationConstraints());

        return $jwt->claims()->get('data');
    }

    /**
     * Retrieves the metadata of the instance.
     */
    public function getInstanceMetadata(): array
    {
        return [
            'company_name' => config('process_intelligence.company_name'),
            'company_database' => config('process_intelligence.company_database'),
        ];
    }

    /**
     * Encodes data using base64 URL encoding.
     */
    private function base64UrlEncode(string $data): string
    {
        // return strtr(base64_encode($data), '+/', '-_');
        return base64_encode($data);
    }
}
