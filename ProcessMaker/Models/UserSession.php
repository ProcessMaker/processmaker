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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
