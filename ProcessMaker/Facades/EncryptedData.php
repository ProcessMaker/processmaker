<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Managers\EncryptedDataManager;

/**
 * @method string encryptText()
 * @method string decryptText()
 * @method void changeKey()
 * @method void setIv()
 * @method string getIv()
 */
class EncryptedData extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return EncryptedDataManager::class;
    }
}
