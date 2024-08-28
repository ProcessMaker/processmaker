<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\EncryptedData;

class EncryptedDataController extends Controller
{
    public function encryptText(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'field_name' => 'required',
            'request_id' => 'required|exists:process_requests,id',
            'plain_text' => 'required',
            'screen_id' => 'required|exists:screens,id',
        ]);

        // Set variables
        $fieldName = $request->input('field_name');
        $requestId = $request->input('request_id');
        $plainText = $request->input('plain_text');
        $screenId = $request->input('screen_id');

        $user = Auth::user();
        $userId = $user->id;

        // Check if the user is assigned to the encrypted field
        EncryptedData::checkUserPermission($userId, $screenId, $fieldName);

        // Encrypt text
        $iv = EncryptedData::generateIv();
        $cipherText = EncryptedData::encryptText($plainText, $iv);

        // Store encrypted data
        $encryptedData = EncryptedData::firstOrNew([
            'field_name' => $fieldName,
            'request_id' => $requestId,
        ]);

        $encryptedData->iv = base64_encode($iv);
        $encryptedData->data = $cipherText;
        $encryptedData->save();

        return $encryptedData->uuid;
    }

    public function decryptText(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'field_name' => 'required',
            'request_id' => 'required|exists:process_requests,id',
            'screen_id' => 'required|exists:screens,id',
        ]);

        // Initialize variables
        $fieldName = $request->input('field_name');
        $requestId = $request->input('request_id');
        $screenId = $request->input('screen_id');

        $user = Auth::user();
        $userId = $user->id;

        // Check if the user is assigned to the encrypted field
        EncryptedData::checkUserPermission($userId, $screenId, $fieldName);

        // Get encrypted data
        $encryptedData = EncryptedData::where([
            'field_name' => $fieldName,
            'request_id' => $requestId,
        ])->firstOrFail();

        $cipherText = $encryptedData->data;
        $iv = base64_decode($encryptedData->iv);

        // Decrypt text
        $plainText = EncryptedData::decryptText($cipherText, $iv);

        return $plainText;
    }
}
