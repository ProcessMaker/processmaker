<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HasUuids;

class UserConfiguration extends ProcessMakerModel
{
    use HasFactory;

    protected $connection = 'processmaker';

    protected $table = 'user_configuration';

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ui_configuration',
    ];

    public static function rules(): array
    {
        return [
            'user_id' => 'required',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the launchpad related
     */
    public function scopeUserConfiguration($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
