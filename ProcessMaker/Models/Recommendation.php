<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
use ProcessMaker\Filters\Filter;

class Recommendation extends ProcessMakerModel
{
    protected $connection = 'processmaker';

    protected $guarded = [];

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

        static::deleting(function (Recommendation $recommendation) {
            $recommendation->recommendationUsers()->delete();
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

    public function baseQuery(User $user)
    {
        // Build the base query
        $query = ProcessRequestToken::query();

        // Scope the query to active (in progress) tasks for the user
        // who just completed/started the task triggering this job
        $query->where('user_id', '=', $user->id)
                ->where('status', '=', 'ACTIVE');

        // Use the Filter class to refine the query with
        // the recommendations advanced filter
        Filter::filter($query, $this->advanced_filter);

        return $query;
    }
}
