<?php

namespace ProcessMaker\EncryptedData;

interface EncryptedDataInterface
{
    /**
     * Encrypting a text using the current driver
     *
     * @param string $plainText The text to encrypt.
     * @param string $iv|null This parameter is always optional when the configured driver is "vault",
     * as the vault handles it internally. It can also be optional when the IV is obtained from the database
     * instead of generating a new one.
     * @param string $key|null This parameter is always optional when the configured driver is "vault",
     * as the vault manages the keys. It is also optional when performing regular encryption with
     * the "local" driver. In this case, the current key is obtained from the environment parameters.
     *
     * @return string
     */
    public function encryptText(string $plainText, string $iv = null, string $key = null): string;

    /**
     * Decrypting a text using the current driver
     *
     * @param string $cipherText The cipher text to decrypt.
     * @param string $iv|null This parameter is always optional when the configured driver is "vault",
     * as the vault handles it internally. It can also be optional when the IV is obtained from the database
     * instead of generating a new one.
     * @param string $key|null This parameter is always optional when the configured driver is "vault",
     * as the vault manages the keys. It is also optional when performing regular decryption with
     * the "local" driver. In this case, the current key is obtained from the environment parameters.
     *
     * @return string
     */
    public function decryptText(string $cipherText, string $iv = null, string $key = null): string;

    /**
     * Update the encrypted texts stored in the database using a new key.
     * In the case of the "local" driver, the new key is updated in the environment variables.
     * In the case of the "vault" driver, the Vault administrator must rotate the key before executing this action.
     */
    public function changeKey(): void;

    /**
     * Set IV value
     */
    public function setIv(string $iv): void;

    /**
     * Get IV value
    */
    public function getIv(): string;
}
