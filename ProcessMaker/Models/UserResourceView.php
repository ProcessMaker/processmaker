<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class UserResourceView extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'viewable_id',
        'viewable_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
