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
     * Expires sessions from other IP addresses different to the active one.
     */
    public static function expiresDuplicatedSessionByIP()
    {
        $usersActiveIP = [];
        self::where('is_active', true)
            ->where('expired_date', null)
            ->orderBy('id', 'desc')
            ->chunk(100, function ($sessions) use (&$usersActiveIP) {
                foreach ($sessions as $session) {
                    $key = $session->user_id . '.' . $session->ip_address;
                    if (!isset($usersActiveIP[$session->user_id])) {
                        $usersActiveIP[$session->user_id] = $key;
                    }
                    // expire all sessions except the ones within the active IP
                    if ($usersActiveIP[$session->user_id] !== $key) {
                        $session->update(['expired_date' => now()]);
                    }
                }
            });
    }

    /**
     * Close all active sessions from other devices different to the active one.
     */
    public static function expiresDuplicatedSessionByDevice()
    {
        $usersActiveDevice = [];
        self::where('is_active', true)
            ->where('expired_date', null)
            ->orderBy('id', 'desc')
            ->chunk(100, function ($sessions) use (&$usersActiveDevice) {
                foreach ($sessions as $session) {
                    $key = $session->user_id . '.' .
                        $session->device_name . '.' .
                        $session->device_type . '.' .
                        $session->device_platform;
                    if (!isset($usersActiveDevice[$session->user_id])) {
                        $usersActiveDevice[$session->user_id] = $key;
                    }
                    // expire all sessions except the ones within the active device
                    if ($usersActiveDevice[$session->user_id] !== $key) {
                        $session->update(['expired_date' => now()]);
                    }
                }
            });
    }
}
