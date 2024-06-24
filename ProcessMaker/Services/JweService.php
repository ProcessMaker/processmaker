<?php

namespace ProcessMaker\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;

class JweService
{
    private $jwtConfig;

    private $jwtSecretKey;

    private $jweEncryptionKey;

    public function __construct()
    {
        $this->jwtSecretKey = base64_decode(config('process_intelligence.secret_key'));
        $this->jweEncryptionKey = base64_decode(config('process_intelligence.encryption_key'));

        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwtSecretKey)
        );
    }

    /**
     * Generates a token with the given data and encrypts it.
     */
    public function generateToken(array $data): string
    {
        $payload = array_merge($data, $this->getInstanceMetadata());

        $now = new DateTimeImmutable();
        $token = $this->jwtConfig->builder()
            ->issuedBy(config('app.url'))
            ->issuedAt($now)
            ->withClaim('data', $payload)
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        $jwt = $token->toString();

        return $this->encrypt($jwt);
    }

    /**
     * Encrypts a JWT using AES-256-GCM encryption.
     *
     * @throws InvalidArgumentException If encryption fails.
     */
    public function encrypt(string $jwt): string
    {
        // Initialization Vector 12 bytes for AES-256-GCM.
        $iv = random_bytes(12);

        // Tag will be filled with the authentication tag by openssl_encrypt.
        $tag = null;

        // Perform the encryption.
        $ciphertext = openssl_encrypt(
            $jwt,
            'aes-256-gcm',
            $this->jweEncryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new InvalidArgumentException('Encryption failed.');
        }

        // Concatenate the IV, tag and ciphertext.
        $combinedData = $iv . $tag . $ciphertext;

        // Encode the combined data in base64.
        // This ensures compatibility with text-based systems and protocols.
        return base64_encode($combinedData);
    }

    /**
     * Validates a token by decrypting it and extracting the data claims.
     */
    public function validateToken(string $token): array
    {
        $decodedToken = $this->decrypt($token);

        $jwt = $this->jwtConfig->parser()->parse($decodedToken);
        assert($jwt instanceof UnencryptedToken);

        return $jwt->claims()->get('data');
    }

    /**
     * Decrypts a JWE (JSON Web Encryption) string.
     *
     * @throws InvalidArgumentException If the decryption fails.
     */
    public function decrypt(string $jwe): string
    {
        $data = base64_decode($jwe);
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $jwt = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->jweEncryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($jwt === false) {
            throw new InvalidArgumentException('Decryption failed.');
        }

        return $jwt;
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
}
