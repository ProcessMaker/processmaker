<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Manager;
use ProcessMaker\EncryptedData\Local;
use ProcessMaker\EncryptedData\Vault;

class EncryptedDataManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'local';
    }

    public function createLocalDriver()
    {
        return new Local();
    }

    public function createVaultDriver()
    {
        return new Vault();
    }
}
