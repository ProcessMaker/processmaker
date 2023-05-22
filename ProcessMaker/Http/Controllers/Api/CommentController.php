<?php

namespace ProcessMaker\Http\Controllers\Api;

use Comment as GlobalComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Comment as CommentResource;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

class CommentController extends Controller
{
    /**
     * A whitelist of attributes that should not be
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
     */
    public function index(Request $request)
    {
        $query = Comment::query()
            ->with('user')
            ->with('children');

        $flag = 'visible';
        if (\Auth::user()->is_administrator) {
            $flag = 'all';
        }
        $query->hidden($flag);

        $commentable_id = $request->input('commentable_id', null);
        $commentable_type = $request->input('commentable_type', null);

        // from a request return comments for the request and their taks
        if ($commentable_type === ProcessRequest::class && $commentable_id) {
            $requestTokens = ProcessRequestToken::where('process_request_id', $commentable_id)->get();
            $tokenIds = $requestTokens->pluck('id');
            $query->where(function ($query) use ($commentable_id) {
                $query->where('commentable_type', ProcessRequest::class)
                        ->where('commentable_id', $commentable_id);
            })->orWhere(function ($query) use ($tokenIds) {
                $query->where('commentable_type', ProcessRequestToken::class)
                        ->whereIn('commentable_id', $tokenIds);
            });
        } else {
            if ($commentable_type) {
                $query->where('commentable_type', $commentable_type);
            }

            if ($commentable_id) {
                $query->where('commentable_id', $commentable_id);
            }
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'created_at'),
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
     */
    public function update(Comment $comment, Request $request)
    {
        if ($comment->user_id !== Auth::user()->id) {
            abort(403);
        }
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
     */
    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== Auth::user()->id) {
            abort(403);
        }

        //delete related comments
        Comment::where('commentable_id', $comment->getKey())
            ->where('commentable_type', Comment::class)
            ->delete();

        $comment->delete();

        return response([], 204);
    }
}
