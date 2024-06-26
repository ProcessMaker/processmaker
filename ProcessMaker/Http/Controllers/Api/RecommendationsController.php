<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Jobs\ApplyRecommendation;
use ProcessMaker\Models\RecommendationUser;

class RecommendationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $results = RecommendationUser::where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('dismissed_until')
                    ->orWhereRaw('dismissed_until < NOW()');
            })
            ->with('recommendation')
            ->get();

        return new ApiCollection($results);
    }

    public function update(Request $request, RecommendationUser $recommendationUser)
    {
        if ($recommendationUser->user_id !== $request->user()->id) {
            return false;
        }

        $action = $request->input('action');

        switch($action) {
            case 'dismiss':
                $recommendationUser->dismiss();
                break;

            case 'reassign_to_user':
                $toUserId = $request->input('to_user_id');
                ApplyRecommendation::dispatch(
                    'reassign_to_user',
                    $recommendationUser->recommendation_id,
                    $recommendationUser->user_id,
                    ['to_user_id' => $toUserId]
                );
                break;

            case 'mark_as_priority':
                ApplyRecommendation::dispatch(
                    'mark_as_priority',
                    $recommendationUser->recommendation_id,
                    $recommendationUser->user_id
                );
                break;

            default:
                break;
        }

        return ['ok' => true];
    }
}
