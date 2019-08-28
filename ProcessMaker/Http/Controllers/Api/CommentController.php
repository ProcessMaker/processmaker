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
     * 
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
     */
    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response([], 204);
    }
}
