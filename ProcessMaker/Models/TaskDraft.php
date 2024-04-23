<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class TaskDraft extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];

    protected $fillable = [
        'task_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function processRequestToken()
    {
        return $this->belongsTo(ProcessRequestToken::class);
    }
}
