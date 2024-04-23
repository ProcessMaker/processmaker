<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use ProcessMaker\Models\ProcessMakerModel;

class InboxRuleLog extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'inbox_rule_attributes' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProcessRequestToken::class, 'process_request_token_id');
    }

    public static function changesSinceQuery($userId, $timestamp)
    {
        return self::where('user_id', $userId)
            ->where('created_at', '>=', $timestamp);
    }

    public static function hasChangesSince($userId, $timestamp)
    {
        return self::changesSinceQuery($userId, $timestamp)->exists();
    }

    public static function changesSince($userId, $timestamp)
    {
        $total = 0;
        $priority = 0;
        $reassigned = 0;
        $draft = 0;
        $submit = 0;

        $logs = self::changesSinceQuery($userId, $timestamp)->limit(10000)->get();

        foreach ($logs as $log) {
            $total++;
            if ($log['inbox_rule_attributes']['make_draft']) {
                $draft++;
            }
            if ($log['inbox_rule_attributes']['submit_data']) {
                $submit++;
            }
            if ($log['inbox_rule_attributes']['mark_as_priority']) {
                $priority++;
            }
            if ($log['inbox_rule_attributes']['reassign_to_user_id']) {
                $reassigned++;
            }
        }

        return compact('total', 'priority', 'reassigned', 'draft', 'submit');
    }
}
