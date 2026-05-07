<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class CaseRetentionPolicyLog extends ProcessMakerModel
{
    use HasFactory;

    protected $table = 'case_retention_policy_logs';

    const UPDATED_AT = null;

    protected $guarded = [
        'id',
        'created_at',
    ];

    protected $fillable = [
        'process_id',
        'case_ids',
        'deleted_count',
        'total_time_taken',
        'deleted_at',
    ];

    protected $casts = [
        'case_ids' => 'array',
    ];
}
