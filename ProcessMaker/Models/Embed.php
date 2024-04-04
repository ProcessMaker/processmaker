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

    /**
     * Save the embed related to the Process
     *
     * @param  Process $process
     * @param array $properties
     * @param string $key
     *
     * @return void
     */
    public function saveProcessEmbed(Process $process, $properties, $key = 'uuid')
    {
        $embed = new Embed();
        // Define the values
        $values = [
            'model_id' => $process->id,
            'model_type' => Process::class,
            'mime_type' => 'text/url',
            'custom_properties' => json_encode([
                'url' => $properties['url'],
                'type' => $properties['type']
            ]),
        ];
        // Review if the uuid was defined
        if (!empty($properties[$key])) {
            $existingEmbed = $embed->where($key, $properties[$key])->first();
            if ($existingEmbed) {
                // Update
                $existingEmbed->update($values);
            } else {
                // Create
                $embed->fill($values)->saveOrFail();
            }
        } else {
            // If the key does not exist create
            $embed->fill($values)->saveOrFail();
        }
    }
}
