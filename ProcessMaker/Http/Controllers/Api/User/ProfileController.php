<?php

namespace ProcessMaker\Http\Controllers\Api\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Facades\UserManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\User;
use ProcessMaker\Transformers\UserTransformer;


/**
 * Controller that handles all Users Profile API endpoints
 *
 */
class ProfileController extends Controller
{

    const DISK_PROFILE = 'profile';

    /**
     * Load profile user
     *
     * @return ResponseFactory|Response Information user
     */
    public function profile()
    {
        $url = '';
        $user = User::find(Auth::id());
        if (!empty($user->avatar) && Storage::disk(self::DISK_PROFILE)->exists($user->avatar)) {
            //Generate url for image
            $url = Storage::disk(self::DISK_PROFILE)->url($user->avatar);
        }
        $user->avatar = $url;
        return fractal($user, new UserTransformer())->respond();
    }

    /**
     * Update Profile user
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws \Throwable
     */
    public function updateProfile(Request $request)
    {
        UserManager::update(User::find(Auth::id()), $request);
        return response([], 200);
    }



}
