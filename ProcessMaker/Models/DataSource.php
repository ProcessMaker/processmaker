<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\Encryptable;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\MakeHttpRequests;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Class DataSource
 *
 * @package ProcessMaker\Packages\Connectors\DataSources\Models
 *
 * @property integer id
 * @property string name
 * @property string description
 * @property array endpoints
 * @property array mappings
 * @property string authtype
 * @property string credentials
 * @property string status
 * @property integer data_source_category_id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="dataSourceEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="endpoints", type="string"),
 *   @OA\Property(property="mappings", type="string"),
 *   @OA\Property(property="authtype", type="string"),
 *   @OA\Property(property="credentials", type="string"),
 *   @OA\Property(property="status", type="string"),
 *   @OA\Property(property="data_source_category_id", type="string"),
 * ),
 * @OA\Schema(
 *   schema="dataSource",
 *   allOf={@OA\Schema(ref="#/components/schemas/dataSourceEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class DataSource extends Model
{

    use Encryptable;
    use MakeHttpRequests;
    use SerializeToIso8601;
    use HideSystemResources;

    protected $connection = 'processmaker';

    protected $encryptable = [
        'credentials'
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
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('data_sources')->ignore($existing);

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
