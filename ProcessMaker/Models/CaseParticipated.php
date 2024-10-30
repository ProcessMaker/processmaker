<?php

namespace ProcessMaker\Models;

use Database\Factories\CaseParticipatedFactory;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Traits\HandlesValueAliasStatus;

class CaseParticipated extends ProcessMakerModel
{
    use HasFactory;
    use HandlesValueAliasStatus;

    protected $table = 'cases_participated';

    protected $fillable = [
        'user_id',
        'case_number',
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
        'processes' => AsCollection::class,
        'requests' => AsCollection::class,
        'request_tokens' => AsCollection::class,
        'tasks' => AsCollection::class,
        'participants' => AsCollection::class,
        'completed_at' => 'datetime:c',
        'initiated_at' => 'datetime:c',
    ];

    protected $attributes = [
        'keywords' => '',
    ];

    protected static function newFactory(): Factory
    {
        return CaseParticipatedFactory::new();
    }

    /**
     * Get the user that owns the case.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
