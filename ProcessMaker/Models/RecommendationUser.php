<?php

namespace ProcessMaker\Models;

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
}
