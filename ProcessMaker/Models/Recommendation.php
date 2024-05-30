<?php

namespace ProcessMaker\Models;

use ProcessMaker\Traits\HasUuids;

class Recommendation extends ProcessMakerModel
{
    use HasUuids;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'min_matches' => 'integer',
        'dismiss_for_secs' => 'integer',
        'actions' => 'array',
        'advanced_filter' => 'json',
    ];

    protected $attributes = [
        'status' => 'ACTIVE',
        'actions' => '[]',
        'min_matches' => 1,
        'dismiss_for_secs' => 604800,
    ];

    protected static function boot(): void
    {
        // Default to an empty array for available actions
        static::saving(static function ($recommendation) {
           $recommendation->actions = $recommendation->actions ?? [];
        });

        parent::boot();
    }

    public function users()
    {
        return $this->hasMany(RecommendationUser::class, 'recommendation_id');
    }
}
