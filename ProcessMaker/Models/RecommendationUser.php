<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Carbon;

class RecommendationUser extends ProcessMakerModel
{
    protected $connection = 'processmaker';

    protected $casts = [
        'dismissed_until' => 'datetime',
        'count' => 'integer',
    ];

    public function recommendation()
    {
        return $this->hasOne(Recommendation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Checks of the dismissed_until timestamp has passed
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return Carbon::parse($this->dismissed_until)->isPast();
    }

    /**
     * Dismiss this user's recommendation for the given amount of time
     *
     * @return void
     */
    public function dismiss(): void
    {
        $dismissFor = $this->recommendation->dismiss_for_secs;

        $dismissUntil = Carbon::now()->addSeconds($dismissFor);

        $this->setAttribute('dismissed_until', $dismissUntil);

        $this->save();
    }
}
