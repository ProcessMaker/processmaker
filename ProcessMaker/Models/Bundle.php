<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class Bundle extends ProcessMakerModel
{
    use HasFactory;

    protected $appends = ['asset_count'];

    protected $casts = [
        'locked' => 'boolean',
        'published' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function assets()
    {
        return $this->hasMany(BundleAsset::class);
    }

    public function getAssetCountAttribute()
    {
        return $this->assets()->count();
    }
}
