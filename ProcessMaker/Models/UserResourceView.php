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

    public static function setViewed($user, $viewableId, $viewableType) {
        return self::firstOrCreate([
            'user_id' => $user->id,
            'viewable_type' => $viewableType,
            'viewable_id' => $viewableId,
        ]);
    }
}
