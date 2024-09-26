<?php

namespace ProcessMaker\EncryptedData;

interface EncryptedDataInterface
{
    public function encryptText(string $plainText, string $iv = null, string $key = null): string;
    
    public function decryptText(string $cipherText, string $iv = null, string $key = null): string;

    public function changeKey(): void;

    public function setIv(string $iv): void;

    public function getIv(): string;
}
