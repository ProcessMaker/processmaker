<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class TaskDraft extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

}
