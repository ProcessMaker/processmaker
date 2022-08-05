<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\UserTokenResource as UserTokenResource;
use ProcessMaker\Models\User;

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
     * Display listing of access tokens for the specified user.
     *
     * @OA\Get(
     *     path="/users/{user_id}/tokens",
     *     summary="Display listing of access tokens for the specified user.",
     *     operationId="getTokens",
     *     tags={"Personal Tokens"},
     *     @OA\Parameter(
     *         description="User id",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of tokens.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/UserToken"),
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
        if (! Auth::user()->can('view', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $tokens = $this->tokenRepository->forUser($user->id);

        $results = $tokens->load('client')->filter(function ($token) {
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
     *
     * @OA\Post(
     *     path="/users/{user_id}/tokens",
     *     summary="Create new token for a specific user",
     *     operationId="createTokens",
     *     tags={"Personal Tokens"},
     *     @OA\Parameter(
     *         description="User id",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="New token instance",
     *         @OA\JsonContent(ref="#/components/schemas/UserToken"),
     *     ),
     * )
     */
    public function store(Request $request, User $user)
    {
        if (! Auth::user()->can('edit', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'scopes' => 'array|in:'.implode(',', Passport::scopeIds()),
        ])->validate();

        $token = $user->createToken(
            $request->name,
            $request->scopes ?: []
        );

        return new UserTokenResource($token);
    }

    /**
     * Show a personal access token for the user
     *
     * @OA\Get(
     *     path="/users/{user_id}/tokens/{token_id}",
     *     summary="Get single token by ID",
     *     operationId="getTokenById",
     *     tags={"Personal Tokens"},
     *     @OA\Parameter(
     *         description="ID of user",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="ID of token to return",
     *         in="path",
     *         name="token_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the token",
     *         @OA\JsonContent(ref="#/components/schemas/UserToken")
     *     ),
     * )
     */
    public function show(Request $request, User $user, $tokenId)
    {
        if (! Auth::user()->can('view', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $token = $this->tokenRepository->findForUser(
            $tokenId,
            $user->getKey()
        );

        if (is_null($token)) {
            return response([], 404);
        }

        return new UserTokenResource($token);
    }

    /**
     * Delete the given token for a user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $tokenId
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/users/{user_id}/tokens/{token_id}",
     *     summary="Delete a token",
     *     operationId="deleteToken",
     *     tags={"Personal Tokens"},
     *     @OA\Parameter(
     *         description="User ID",
     *         in="path",
     *         name="user_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="Token ID",
     *         in="path",
     *         name="token_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success"
     *     ),
     * )
     */
    public function destroy(Request $request, User $user, $tokenId)
    {
        if (! Auth::user()->can('edit', $user)) {
            throw new AuthorizationException(__('Not authorized to update this user.'));
        }

        $token = $this->tokenRepository->findForUser(
            $tokenId,
            $user->getKey()
        );

        if (is_null($token)) {
            return abort(404);
        }

        $token->revoke();

        return response([], 204);
    }
}
