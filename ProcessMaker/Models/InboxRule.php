<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ProcessMaker\Jobs\SmartInboxExistingTasks;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;

class InboxRule extends ProcessMakerModel
{
    use HasFactory;

    protected $table = 'inbox_rules';

    protected $casts = [
        'data' => 'object',
        'submit_button' => 'array',
        'end_date' => 'datetime',
        'active' => 'boolean',
        'mark_as_priority' => 'boolean',
        'make_draft' => 'boolean',
        'submit_data' => 'boolean',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Delete the saved search when deleting an inbox rule
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function (InboxRule $inboxRule) {
            $inboxRule->savedSearch()->delete();
        });
    }

    /**
     * Define the relationship with ProcessRequestToken model
     *
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProcessRequestToken::class, 'process_request_token_id');
    }

    /**
     * Define the relationship with SavedSearch model
     *
     * @return BelongsTo
     */
    public function savedSearch(): BelongsTo
    {
        return $this->belongsTo(SavedSearch::class, 'saved_search_id');
    }

    /**
     * Start a job to apply actions to all in-progress tasks
     *
     * @return void
     */
    public function applyToExistingTasks()
    {
        SmartInboxExistingTasks::dispatch($this->id);
    }

    public static function createSavedSearch(array $data)
    {
        $userId = $data['user_id'];

        return SavedSearch::create([
            'user_id' => $userId,
            'title' => 'Inbox Rule Saved Search',
            'meta' => ['columns' => $data['columns']],
            'pmql' => $data['pmql'],
            'type' => 'task',
            'advanced_filter' => $data['advanced_filter'],
            'is_system' => true,
            'key' => 'inbox-rule',
        ]);
    }
}
