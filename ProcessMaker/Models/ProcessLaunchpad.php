<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Traits\HasUuids;
use ProcessMaker\Traits\Exportable;

class ProcessLaunchpad extends ProcessMakerModel
{
    use Exportable;
    use HasFactory;
    use HasUuids;

    protected $connection = 'processmaker';

    protected $table = 'process_launchpad';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'process_id',
        'properties',
    ];

    public static function rules(): array
    {
        return [
            'user_id' => 'required',
            'process_id' => 'required',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id');
    }

    /**
     * Get the launchpad related
     */
    public static function getLaunchpad($showLaunchpad, $proId)
    {
        $response = null;
        if ($showLaunchpad) {
            $response = self::where('process_id', $proId)->first();
        }

        return $response;
    }
}
