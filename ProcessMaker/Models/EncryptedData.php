<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\ValidationException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\Screen;
use ProcessMaker\Traits\HasUuids;

class EncryptedData extends ProcessMakerModel
{
    use HasUuids;

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
                'encrypted_field_not_exists' => __("There is no field with the name ':fieldName'.", ['fieldName' => $fieldName]),
            ]);
        }
        if (is_null($encryptedField->encryptedConfig)) {
            throw ValidationException::withMessages([
                'encrypted_field_config_empty' => __('Configuration related to encryption is not found for this field.'),
            ]);
        }
        if (is_null($encryptedField->encryptedConfig['encrypted'])) {
            throw ValidationException::withMessages([
                'encrypted_field_encryption_disabled' => __('Encryption is not enabled for this field.'),
            ]);
        }
        if (empty($encryptedField->encryptedConfig['assignments']['users']) && empty($encryptedField->encryptedConfig['assignments']['groups'])) {
            throw ValidationException::withMessages([
                'encrypted_field_assignments_empty' => __('Configuration related to assignments is missing for this field.'),
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
                'encrypted_field_assignments_invalid' => __('You are not assigned to this encrypted field.'),
            ]);
        }
    }
}
