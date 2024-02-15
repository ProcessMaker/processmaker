<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\ProcessRequestToken;
class InboxRule extends ProcessMakerModel
{
    use HasFactory;

    protected $table = 'inbox_rules';

    /**
     * Define the relationship with ProcessRequestToken model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(ProcessRequestToken::class, 'process_request_id');
    }
}
