<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

class DataSourceCategory extends Model
{
    use SerializeToIso8601;
    use HideSystemResources;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'status',
        'is_system'
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('data_source_categories')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique],
            'status' => 'required|string|in:ACTIVE,INACTIVE'
        ];
    }

    /**
     * Get screens
     *
     * @return HasMany
     */
    public function datasources()
    {
        return $this->hasMany(DataSource::class);
    }
}
