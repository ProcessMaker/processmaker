<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class MediaLog extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];

    protected $fillable = [
        'media_id',
        'user_id',
        'event_type',
    ];

    public function media() {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
