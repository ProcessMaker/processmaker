<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class BundleInstance extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'instance_url',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }
}
