<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class DataSourceIntegrations extends Model
{
    use HasFactory;

    protected $table = 'data_source_integrations';

    protected $fillable = ['name', 'key', 'base_url', 'auth_type', 'credentials'];

    public static function rules()
    {
        return [
            'name' => 'required',
            'key' => 'required',
            'base_url' => 'required',
            'auth_type' => 'required',
            'credentials' => 'required',
        ];
    }

    public function getCredentialsAttribute($value)
    {
        if (!$value) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);

            return json_decode($decrypted, true) ?? $decrypted;
        } catch (\Exception $e) {
            \Log::debug('======== DECRYPTION ERROR ====== ', ['$error' => $e->getMessage()]);

            return null;
        }
    }

    public function setCredentialsAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        try {
            $encrypted = Crypt::encryptString($value);
            $this->attributes['credentials'] = $encrypted;
        } catch (\Exception $e) {
            \Log::debug('======== ENCRYPTION ERROR ====== ', ['$error' => $e->getMessage()]);
        }
    }
}
