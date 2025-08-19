<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\User;

trait HasComments
{
    /**
     * Get all comments for the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Add a comment to the model.
     *
     * @param string $body
     * @param int|null $userId
     * @param string|null $subject
     * @param string $type
     * @param string|null $caseNumber
     * @return \ProcessMaker\Models\Comment
     */
    public function addComment(string $body, ?User $user = null, ?string $subject = null, string $type = 'LOG', ?string $caseNumber = null)
    {
        $comment = new Comment([
            'body' => $body,
            'user_id' => $user->getKey(),
            'subject' => $subject,
            'type' => $type,
            'case_number' => $caseNumber,
            'commentable_type' => get_class($this),
            'commentable_id' => $this->getKey(),
        ]);

        return $this->comments()->save($comment);
    }
}
