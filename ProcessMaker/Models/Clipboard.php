<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class Clipboard extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'config',
        'user_id',
        'type',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'config' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
