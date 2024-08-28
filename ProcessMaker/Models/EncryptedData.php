<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Str;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Traits\HasUuids;

class EncryptedData extends ProcessMakerModel
{
    use HasUuids;

    const ENCRYPTION_METHOD = 'aes-256-cbc';

    protected $connection = 'processmaker';

    protected $table = 'encrypted_data';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'field_name',
        'request_id',
        'iv',
        'data',
    ];

    /**
     * Check user permission for the encrypted data
     * 
     * @param string $userId
     * @param string $screenId
     * @param string $fieldName
     */
    public static function checkUserPermission($userId, $screenId, $fieldName)
    {
        // To Do: Complete check if the user is assigned to the encrypted field in the screen
    }

    /**
     * Get encrypted data key value.
     *
     * @return string
     */
    public static function getEncryptedDataKey()
    {
        $key = config('app.encrypted_data_key');
        $prefix = 'base64:';

        $key = base64_decode(Str::after($key, $prefix));

        return $key;
    }

    /**
     * Generate an iv value.
     *
     * @return string
     */
    public static function generateIv()
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));

        return $iv;
    }

    /**
     * Encrypt text.
     *
     * @param string $plainText
     * @param string $iv
     * @return string
     */
    public static function encryptText($plainText, $iv)
    {
        $key = self::getEncryptedDataKey();

        $cipherText = openssl_encrypt($plainText, self::ENCRYPTION_METHOD, $key, 0, $iv);

        return $cipherText;
    }

    /**
     * Decrypt text.
     *
     * @param string $cipherText
     * @param string $iv
     * @return string
     */
    public static function decryptText($cipherText, $iv)
    {
        $key = self::getEncryptedDataKey();

        $plainText = openssl_decrypt($cipherText, self::ENCRYPTION_METHOD, $key, 0, $iv);

        return $plainText;
    }
}
