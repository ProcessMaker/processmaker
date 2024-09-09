<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use ProcessMaker\Models\ProcessMakerModel;

class CaseStarted extends ProcessMakerModel
{
    protected $table = 'cases_started';

    protected $fillable = [
        'case_number',
        'user_id',
        'case_title',
        'case_title_formatted',
        'case_status',
        'processes',
        'requests',
        'request_tokens',
        'tasks',
        'participants',
        'initiated_at',
        'completed_at',
        'keywords',
    ];

    protected $casts = [
        'processes' => AsArrayObject::class,
        'requests' => AsArrayObject::class,
        'request_tokens' => AsArrayObject::class,
        'tasks' => AsArrayObject::class,
        'participants' => AsArrayObject::class,
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the case.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
