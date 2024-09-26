<?php

namespace ProcessMaker\EncryptedData;

use GuzzleHttp\Client;
use ProcessMaker\EncryptedData\EncryptedDataInterface;
use ProcessMaker\Models\EncryptedData;
use VaultPHP\Authentication\Provider\Token;
use VaultPHP\SecretEngines\Engines\Transit\Request\DecryptData\DecryptDataRequest;
use VaultPHP\SecretEngines\Engines\Transit\Request\EncryptData\EncryptDataRequest;
use VaultPHP\SecretEngines\Engines\Transit\Transit;
use VaultPHP\VaultClient;

class Vault implements EncryptedDataInterface
{
    public static $iv = '';

    private $host;
    private $token;
    private $transitKey;

    public function __construct()
    {
        // Get config
        $this->host = config('app.encrypted_data.vault_host');
        $this->token = config('app.encrypted_data.vault_token');
        $this->transitKey = config('app.encrypted_data.vault_transit_key');
    }

    /**
     * Encrypt text.
     *
     * @param string $plainText
     * @param string $iv
     * @param string $key
     * @return string
     */
    public function encryptText(string $plainText, string $iv = null, string $key = null): string
    {
        // Initialize required objects
        $transitApi = $this->buildTransitApi();
        $encryptDataRequest = new EncryptDataRequest($this->transitKey, $plainText);

        // Encrypt text
        $response = $transitApi->encryptData($encryptDataRequest);
        $cipherText = $response->getCiphertext();

        return $cipherText;
    }

    /**
     * Decrypt text.
     *
     * @param string $cipherText
     * @param string $iv
     * @param string $key
     * @return string
     */
    public function decryptText(string $cipherText, string $iv = null, string $key = null): string
    {
        // Initialize required objects
        $transitApi = $this->buildTransitApi();
        $decryptDataRequest = new DecryptDataRequest($this->transitKey, $cipherText);

        // Decrypt text
        $response = $transitApi->decryptData($decryptDataRequest);
        $plainText = $response->getPlaintext();

        return $plainText;
    }

    /**
     * Change key and update encrypted texts
     */
    public function changeKey(): void
    {
        // Get all encrypted data
        $records = EncryptedData::select(['id', 'data'])->get();

        // Change values in all records
        foreach ($records as $record) {
            // Decrypt text
            $plainText = self::decryptText($record->data);

            // Encrypt text with new key
            $cipherText = self::encryptText($plainText);

            // Store new values
            $record->data = $cipherText;
            $record->save(); 
        }
    }

    /**
     * Set IV value
     *
     * @param string $iv
     */
    public function setIv(string $iv): void
    {
        self::$iv = $iv;
    }

    /**
     * Get IV value
     *
     * @return string
     */
    public function getIv(): string
    {
        return self::$iv;
    }

    /**
     * Build transit API object
     *
     * @return \VaultPHP\SecretEngines\Engines\Transit\Transit
     */
    private function buildTransitApi()
    {
        $httpClient = new Client();
        $auth = new Token($this->token);
        $client = new VaultClient($httpClient, $auth, $this->host);
        $transitApi = new Transit($client);

        return $transitApi;
    }
}
