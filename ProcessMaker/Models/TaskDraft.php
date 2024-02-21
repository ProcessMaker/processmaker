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

    protected $casts = [
        'data' => 'array',
    ];
}
