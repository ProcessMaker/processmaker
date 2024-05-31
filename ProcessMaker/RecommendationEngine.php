<?php

namespace ProcessMaker;

use ProcessMaker\Models\User;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\RecommendationUser;
use ProcessMaker\Models\ProcessRequestToken;

class RecommendationEngine
{
    /**
     * Target user's recommendations
     *
     * @var \ProcessMaker\Models\ProcessRequestToken|\ProcessMaker\Models\User
     */
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Returns an instance of the RecommendationEngine for the given user
     *
     * @param  \ProcessMaker\Models\User  $user
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
        $recommendations = Recommendation::active()->get();

        foreach ($recommendations as $recommendation) {

            // Build the base query
            $query = ProcessRequestToken::query();

            // Scope the query to active (in progress) tasks for the user
            // who just completed/started the task triggering this job
            $query->where('user_id', '=', $this->user->id)
                  ->where('status','=','ACTIVE');

            // Use the Filter class to refine the query with
            // the recommendations advanced filter
            Filter::filter($query, $recommendation->advanced_filter);

            // Set up the RecommendationUser query
            $recommendationUsersQuery = $recommendation->recommendationUsers()->where('user_id', '=', $this->user->id);

            // Check if this RecommendationUser exists
            $recommendationUsersExists = $recommendationUsersQuery->exists();

            // Check if there are enough results to satisfy the
            // minimum matches required by the recommendation
            $count = $query->count();
            $minimumMatchesMet = $count >= $recommendation->min_matches;

            // If we find the RecommendationUser records, we need
            // to make sure they're up-to-date
            if ($recommendationUsersExists) {
                foreach ($recommendationUsersQuery->get() as $recommendationUser) {
                    // If the minimum matches are met, then we can
                    // update the existing RecommendationUser records
                    if ($minimumMatchesMet) {
                        // Update the matching count
                        $recommendationUser->count = $count;

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
                        $recommendationUsersQuery->delete();
                    }
                }
            } else if ($minimumMatchesMet) {
                // If the minimum number of matches is satisfied and the RecommendationUser
                // records don't exist, we need to create them
                $recommendationUser = (new RecommendationUser())->fill([
                    'count' => $count,
                    'user_id' => $this->user->id,
                    'recommendation_id' => $recommendation->id,
                ]);

                $recommendationUser->save();
            }
        }
    }
}
