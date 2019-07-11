<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\UserTokenResource as UserTokenResource;
use ProcessMaker\Models\User;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\Passport;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;


class UserTokenController extends Controller
{
    /**
     * The token repository implementation.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * The validation factory implementation.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validation;

    /**
     * Create a controller instance.
     *
     * @param  \Laravel\Passport\TokenRepository  $tokenRepository
     * @param  \Illuminate\Contracts\Validation\Factory  $validation
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, ValidationFactory $validation)
    {
        $this->validation = $validation;
        $this->tokenRepository = $tokenRepository;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     *
     *     @OA\Get(
     *     path="/users",
     *     summary="Returns all tokens for the specified user",
     *     operationId="getUserTokens",
     *     tags={"Tokens"},
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of user tokens",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/users"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request, User $user)
    {
        if (!Auth::user()->can('view', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $tokens = $this->tokenRepository->forUser($request->user()->getKey());

        $results =  $tokens->load('client')->filter(function ($token) {
            return $token->client->personal_access_client && ! $token->revoked;
        })->values();

        // Paginate
        $page = Paginator::resolveCurrentPage() ?: 1;
        $perPage = $request->input('per_page', 10);
        $results = new LengthAwarePaginator($results->forPage($page, $perPage), $results->count(), $perPage, $page);

        return new ApiCollection($results);
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function store(Request $request, User $user)
    {
        if (!Auth::user()->can('edit', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'scopes' => 'array|in:'.implode(',', Passport::scopeIds()),
        ])->validate();

        $token =  $user->createToken(
            $request->name, $request->scopes ?: []
        );

        return new UserTokenResource($token);

    }

    /**
     * Show a personal access token for the user
     */
    public function show(Request $request, User $user, $tokenId)
    {
        if (!Auth::user()->can('view', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $token = $this->tokenRepository->findForUser(
            $tokenId, $user->getKey()
        );

        if (is_null($token)) {
            return new Response('', 404);
        }
       return new UserTokenResource($token);
    }

    /**
     * Delete the given token for a user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $tokenId
     * @return \Illuminate\Http\Response
     */

    public function destroy(Request $request, User $user, $tokenId)
    {
        if (!Auth::user()->can('edit', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $token = $this->tokenRepository->findForUser(
            $tokenId, $user->getKey()
        );

        if (is_null($token)) {
            return abort(404);
        }

        $token->revoke();

        return response([], 204);
    }


}
