<?php

namespace ProcessMaker;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use ProcessMaker\Jobs\GenerateUserRecommendations;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\RecommendationUser;
use ProcessMaker\Models\User;

class RecommendationEngine
{
    /**
     * Target user's recommendations
     *
     * @var User
     */
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Returns an instance of the RecommendationEngine for the given user
     *
     * @param  User  $user
     *
     * @return static
     */
    public static function for(User $user): static
    {
        return new static($user);
    }

    /**
     * Generate the recommendations for the given user
     *
     * @return void
     */
    public function generate(): void
    {
        $this->debug("Generating recommendations for user {$this->user->id}");

        if (static::disabled()) {
            return;
        }

        foreach (Recommendation::active()->get() as $recommendation) {
            $query = $recommendation->baseQuery($this->user);

            // Set up the RecommendationUser query
            $recommendationUsersQuery = $recommendation->recommendationUsers()->where('user_id', '=', $this->user->id);

            // Check if this RecommendationUser exists
            $recommendationUsersExists = $recommendationUsersQuery->exists();

            if ($recommendationUsersExists) {
                $this->debug("RecommendationUser {$recommendation->id} exists for user {$this->user->id}");
            } else {
                $this->debug("RecommendationUser {$recommendation->id} does not exist for user {$this->user->id}");
            }

            // Check if there are enough results to satisfy the
            // minimum matches required by the recommendation
            $minimumMatchesMet = $this->minimumMatchesMet($recommendation, ($count = $query->count()));

            $this->debug("Recommendation {$recommendation->id} matches {$count} results");

            if ($recommendationUsersExists) {
                // If we find the RecommendationUser records, we need
                // to make sure they're up-to-date
                $this->debug('Modifying existing RecommendationUser records');
                $this->modifyExisting($recommendationUsersQuery, $minimumMatchesMet, $count);
            } elseif ($minimumMatchesMet) {
                // If the minimum number of matches is satisfied and the RecommendationUser
                // records don't exist, we need to create them
                $this->debug('Creating new RecommendationUser records');
                $this->create($recommendation, $count);
            } else {
                $this->debug(
                    "Recommendation {$recommendation->id} does not meet minimum: {$recommendation->min_matches}"
                );
            }
        }
    }

    /**
     * Create a new RecommendationUser
     *
     * @param  Recommendation  $recommendation
     * @param  int  $count
     *
     * @return void
     */
    protected function create(Recommendation $recommendation, int $count): void
    {
        $recommendationUser = (new RecommendationUser())->fill([
            'count' => $count,
            'user_id' => $this->user->id,
            'recommendation_id' => $recommendation->id,
        ]);

        $recommendationUser->save();
    }

    /**
     * Update or delete existing RecommendationUser records
     *
     * @param  HasMany|Builder  $query
     * @param  bool  $minimumMatchesMet
     * @param  int  $resultCount
     *
     * @return void
     */
    protected function modifyExisting(HasMany|Builder $query, bool $minimumMatchesMet, int $resultCount): void
    {
        foreach ($query->get() as $recommendationUser) {
            // If the minimum matches are met, then we can
            // update the existing RecommendationUser records
            if ($minimumMatchesMet) {
                // Update the matching count
                $recommendationUser->count = $resultCount;

                // Check if the dismissed_until has passed
                if ($recommendationUser->isExpired()) {
                    $recommendationUser->setAttribute('dismissed_until', null);
                }

                // Persist the updates
                $recommendationUser->save();
            } else {
                // Otherwise, if the minimum matches are not met, we
                // need to delete the existing RecommendationUser
                // rows since they are now invalid
                $query->delete();
            }
        }
    }

    /**
     * Check if the matches/result count satisfies the threshold set for the Recommendation
     *
     * @param  Recommendation  $recommendation
     * @param  int  $count
     *
     * @return bool
     */
    protected function minimumMatchesMet(Recommendation $recommendation, int $count): bool
    {
        return $count >= $recommendation->min_matches;
    }

    /**
     * Indicates if the RecommendationEngine is turned on or off globally
     *
     * @return bool
     */
    public static function disabled(): bool
    {
        return config('app.recommendations_enabled') === false;
    }

    public static function shouldGenerateFor(User|null $user): bool
    {
        if (!$user || self::disabled()) {
            return false;
        }

        if (isset($user->meta->disableRecommendations) && $user->meta->disableRecommendations) {
            return false;
        }

        return true;
    }

    public function debug(string $message, array $params = []): void
    {
        if (config('app.debug')) {
            \Log::debug($message, $params);
        }
    }

    public static function handleUserSettingChanges(User $user, array $originalAttributes): void
    {
        $originalMeta = $originalAttributes['meta'] ?? null;
        $newMeta = $user->meta ?? null;

        $originalDisableRecommendations = $originalMeta?->disableRecommendations ?? false;
        $newDisableRecommendations = $newMeta?->disableRecommendations ?? false;

        if ($originalDisableRecommendations && !$newDisableRecommendations) {
            // The user enabled recommendations. Generate new recommendations.
            GenerateUserRecommendations::dispatch($user->id);
        }

        if (!$originalDisableRecommendations && $newDisableRecommendations) {
            // The user disabled recommendations. Delete all existing recommendations.
            RecommendationUser::deleteFor($user);
        }
    }
}
