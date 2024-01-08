<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class UserSession extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'user_agent',
        'ip_address',
        'device_name',
        'device_type',
        'device_platform',
        'device_browser',
        'token',
        'is_active',
        'expired_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expired_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Expires duplicate active sessions from the same IP address.
     *
     * Close all active sessions from the same IP address except the last one.
     */
    public static function expiresDuplicatedSessionByIP()
    {
        $userIP = [];
        self::where('is_active', true)
            ->where('expired_date', null)
            ->orderBy('id', 'desc')
            ->chunk(100, function ($sessions) use (&$userIP) {
                foreach ($sessions as $session) {
                    $key = $session->user_id . '.' . $session->ip_address;
                    if (in_array($key, $userIP)) {
                        $session->update(['expired_date' => now()]);
                    } else {
                        // keep first session by user and ip
                        $userIP[] = $key;
                    }
                }
            });
    }

    /**
     * Expires duplicate active sessions from the same device.
     *
     * Close all active sessions from the same device except the last one.
     */
    public static function expiresDuplicatedSessionByDevice()
    {
        $userDevice = [];
        self::where('is_active', true)
            ->where('expired_date', null)
            ->orderBy('id', 'desc')
            ->chunk(100, function ($sessions) use (&$userDevice) {
                foreach ($sessions as $session) {
                    $key = $session->user_id . '.'.
                        $session->device_name . '.' .
                        $session->device_type . '.' .
                        $session->device_platform;
                    if (in_array($key, $userDevice)) {
                        $session->update(['expired_date' => now()]);
                    } else {
                        // keep first session by user and device
                        $userDevice[] = $key;
                    }
                }
            });
    }
}
