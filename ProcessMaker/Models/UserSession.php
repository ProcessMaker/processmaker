<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class UserSession extends ProcessMakerModel
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
