<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class BundleSetting extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'setting',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function export()
    {
        return [
            'setting' => $this->setting,
            'config' => $this->config,
        ];
    }
}
