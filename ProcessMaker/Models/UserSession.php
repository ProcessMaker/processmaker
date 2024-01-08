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
     * @param string $ip The IP address to check for duplicate sessions.
     */
    public static function expiresDuplicatedSessionByIP($ip)
    {
        $userIP = [];
        self::where('ip_address', $ip)
            ->where('is_active', true)
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
}
