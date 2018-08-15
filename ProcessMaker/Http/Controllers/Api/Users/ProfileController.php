<?php

namespace ProcessMaker\Http\Controllers\Api\Users;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /**
     * Load profile user
     *
     * @return ResponseFactory|Response Information user
     */
    public function profile()
    {
        $user = User::find(Auth::id());
        $user->avatar = UserManager::getUrlAvatar($user);
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
