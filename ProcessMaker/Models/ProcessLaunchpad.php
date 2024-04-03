<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\HasUuids;

class ProcessLaunchpad extends ProcessMakerModel
{
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
        'launchpad_properties'
    ];

    public static function rules(): array
    {
        return [
            'user_id' => 'required',
            'process_id' => 'required',
        ];
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
        $result = [];
        if ($showLaunchpad) {
            $response = self::where('process_id', $proId)->first();
            if (!is_null($response)) {
                $result = $response;
            }
        }

        return $result ;
    }
}
