<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class Embed extends ProcessMakerModel
{
    use HasFactory;

    protected $connection = 'processmaker';

    protected $table = 'embed';

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
