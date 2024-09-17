<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\Screen;
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
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function checkUserPermission($userId, $screenId, $fieldName)
    {
        // Get screen fields
        $screen = Screen::find($screenId);
        $fields = $screen->getFieldsAttribute()->toArray();

        // Search encrypted fields
        $encryptedFields = array_filter($fields, function ($field) use ($fieldName) {
            return $field->field === $fieldName;
        });

        // Use the first definition
        $encryptedField = array_pop($encryptedFields) ?? null;

        // Run validations
        if (is_null($encryptedField)) {
            throw ValidationException::withMessages([
                'encrypted_field_not_exists' => 'Not exists a field with name "' . $fieldName . '".',
            ]);
        }
        if (is_null($encryptedField->encryptedConfig)) {
            throw ValidationException::withMessages([
                'encrypted_field_config_empty' => 'Config related to encryption is not found for this field.',
            ]);
        }
        if (is_null($encryptedField->encryptedConfig['encrypted'])) {
            throw ValidationException::withMessages([
                'encrypted_field_encryption_disabled' => 'Config related to encryption is not enabled for this field.',
            ]);
        }
        if (empty($encryptedField->encryptedConfig['assignments']['users']) && empty($encryptedField->encryptedConfig['assignments']['groups'])) {
            throw ValidationException::withMessages([
                'encrypted_field_assignments_empty' => 'Config related to assignments is missing for this field.',
            ]);
        }

        // Get groups and users from config
        $groupsAssigned = $encryptedField->encryptedConfig['assignments']['groups'];
        $usersAssigned = $encryptedField->encryptedConfig['assignments']['users'];

        // Get consolidated users list
        $process = new Process();
        $usersAssigned = $process->getConsolidatedUsers($groupsAssigned, $usersAssigned);

        // Validate if current user have permissions for this cencrypted field
        if (!in_array($userId, $usersAssigned)) {
            throw ValidationException::withMessages([
                'encrypted_field_assignments_invalid' => 'User is not assigned to this encrypted field.',
            ]);
        }
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
