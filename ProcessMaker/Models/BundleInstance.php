<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class BundleInstance extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'instance_url',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }
}
