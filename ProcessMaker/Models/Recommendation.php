<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;

class Recommendation extends ProcessMakerModel
{
    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'min_matches' => 'integer',
        'dismiss_for_secs' => 'integer',
        'actions' => 'array',
        'advanced_filter' => 'array',
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

    public function recommendationUsers()
    {
        return $this->hasMany(RecommendationUser::class, 'recommendation_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', '=', 'ACTIVE');
    }
}
