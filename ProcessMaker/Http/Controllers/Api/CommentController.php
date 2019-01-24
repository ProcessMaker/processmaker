<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Comment as CommentResource;
use ProcessMaker\Models\Comment;

class CommentController extends Controller
{
    /**
     * A blacklist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        //
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \ProcessMaker\Http\Resources\ApiCollection
     *
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/comments",
     *     summary="Returns all comments for a given type",
     *     operationId="getComments",
     *     tags={"Comments"},
     *     @OA\Parameter(ref="#/components/parameters/commentable_id"),
     *     @OA\Parameter(ref="#/components/parameters/commentable_type"),
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of comments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/comments"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = Comment::query()
            ->with('user');

        $flag = 'visible';
        if (\Auth::user()->is_administrator) {
            $flag = 'all';
        }
        $query->hidden($flag);

        $commentable_id = $request->input('commentable_id', null);
        if ($commentable_id) {
            $query->where('commentable_id', $commentable_id);
        }

        $commentable_type = $request->input('commentable_type', null);
        if ($commentable_type) {
            $query->where('commentable_type', $commentable_type);
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'updated_at'),
                $request->input('order_direction', 'ASC')
            )->paginate($request->input('per_page', 100));

        return new ApiCollection($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/comments",
     *     summary="Save a new comment",
     *     operationId="createComments",
     *     tags={"Comments"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/commentsEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/comments")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $data['user_id'] = Auth::user()->id;
        $request->merge($data);
        $request->validate(Comment::rules());

        $comment = new Comment();
        $comment->fill($request->input());
        $comment->saveOrFail();

        return response(new CommentResource($comment), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Comment $comment
     *
     * @return CommentResource
     *
     * @OA\Get(
     *     path="/comments/commentId",
     *     summary="Get single comment by ID",
     *     operationId="getCommentById",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         description="ID of comments to return",
     *         in="path",
     *         name="comment_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the comments",
     *         @OA\JsonContent(ref="#/components/schemas/comments")
     *     ),
     * )
     */
    public function show(Comment $comment)
    {
        return new CommentResource($comment);
    }

    /**
     * Update a comment
     *
     * @param Comment $comment
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     *
     * @OA\Put(
     *     path="/comments/commentId",
     *     summary="Update a comment",
     *     operationId="updateComment",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         description="ID of comment to return",
     *         in="path",
     *         name="comment_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/commentsEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/comments")
     *     ),
     * )
     */
    public function update(Comment $comment, Request $request)
    {
        $data['user_id'] = Auth::user()->id;
        $request->merge($data);
        $request->validate(Comment::rules());

        $comment->fill($request->input());
        $comment->saveOrFail();
        return response([], 204);
    }

    /**
     * Delete comment
     *
     * @param Comment $comment
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *
     * @throws \Exception
     *
     * @OA\Delete(
     *     path="/comments/id",
     *     summary="Delete a comments",
     *     operationId="deleteComments",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         description="ID of comments to return",
     *         in="path",
     *         name="comment_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/comments")
     *     ),
     * )
     */
    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response([], 204);
    }
}
