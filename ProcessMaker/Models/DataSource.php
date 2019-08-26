<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{

    protected $connection = 'processmaker';

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('data_source')->ignore($existing);

        return [
            'name' => ['required', $unique],
            'authtype' => 'required',
            'status' => 'in:ACTIVE,INACTIVE',
            'data_source_category_id' => 'required',
        ];
    }

    /**
     * Get the associated category
     */
    public function category()
    {
        return $this->belongsTo(DataSourceCategory::class, 'data_source_category_id');
    }
}
