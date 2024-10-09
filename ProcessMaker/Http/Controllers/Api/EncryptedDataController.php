<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\EncryptedData as EncryptedDataManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\EncryptedData;

class EncryptedDataController extends Controller
{
    public function encryptText(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'uuid' => 'nullable|uuid',
            'field_name' => 'required',
            'plain_text' => 'required',
            'screen_id' => 'required|exists:screens,id',
        ]);

        // Set variables
        $uuid = $request->input('uuid');
        $fieldName = $request->input('field_name');
        $plainText = $request->input('plain_text');
        $screenId = $request->input('screen_id');

        // Get current user
        $user = Auth::user();
        $userId = $user->id;

        // Check if the user is assigned to the encrypted field
        EncryptedData::checkUserPermission($userId, $screenId, $fieldName);

        // Get configured driver for encrypted data
        $driver = config('app.encrypted_data.driver');

        // Encrypt text
        $cipherText = EncryptedDataManager::driver($driver)->encryptText($plainText);

        // Store encrypted data
        $encryptedData = EncryptedData::firstOrNew([
            'uuid' => $uuid,
        ]);

        $encryptedData->uuid = $uuid;
        $encryptedData->field_name = $fieldName;
        $encryptedData->iv = base64_encode(EncryptedDataManager::driver($driver)->getIv());
        $encryptedData->data = $cipherText;
        $encryptedData->save();

        return $encryptedData->uuid;
    }

    public function decryptText(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'uuid' => 'required|uuid',
            'field_name' => 'required',
            'screen_id' => 'required|exists:screens,id',
        ]);

        // Set variables
        $uuid = $request->input('uuid');
        $fieldName = $request->input('field_name');
        $screenId = $request->input('screen_id');

        // Get current user
        $user = Auth::user();
        $userId = $user->id;

        // Check if the user is assigned to the encrypted field
        EncryptedData::checkUserPermission($userId, $screenId, $fieldName);

        // Get encrypted data
        $encryptedData = EncryptedData::where('uuid', $uuid)->firstOrFail();

        $cipherText = $encryptedData->data;
        $iv = base64_decode($encryptedData->iv);

        // Get configured driver for encrypted data
        $driver = config('app.encrypted_data.driver');

        // Set IV
        EncryptedDataManager::driver($driver)->setIv($iv);

        // Decrypt text
        $plainText = EncryptedDataManager::driver($driver)->decryptText($cipherText);

        return $plainText;
    }
}
