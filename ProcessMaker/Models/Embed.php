<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Traits\HasUuids;

class Embed extends ProcessMakerModel
{
    use HasFactory;
    use HasUuids;

    protected $connection = 'processmaker';

    protected $table = 'embed';

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
        'model_id',
        'model_type',
        'mime_type',
        'custom_properties',
    ];

    public static function rules(): array
    {
        return [
            'model_id' => 'required',
            'model_type' => 'required',
            'mime_type' => 'required',
            'custom_properties' => 'required',
        ];
    }

    public function process()
    {
        return $this->hasMany(Process::class, 'id');
    }
}
