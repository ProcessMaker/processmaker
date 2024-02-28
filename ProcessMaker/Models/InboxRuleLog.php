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
}
