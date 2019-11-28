<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\Encryptable;
use ProcessMaker\Traits\HasCategories;
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
 * @property Carbon $updated_at
 * @property Carbon $created_at
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
    use HasCategories;

    const categoryClass = DataSourceCategory::class;

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

    protected $casts = [
        'endpoints' => 'array',
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
            'authtype' => 'required|in:NONE,BASIC,OAUTH2_BEARER,OAUTH2_PASSWORD',
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

    /**
     * Set multiple|single categories to the data source
     *
     * @param string $value the value is a comma separated list of category ids
     *
     * @return DataSource
     */
    public function setDataSourceCategoryIdAttribute($value)
    {
        return $this->setMultipleCategories($value, 'data_source_category_id');
    }

    /**
     * Get multiple|single categories of the data source
     *
     * @param string $value
     *
     * @return DataSource
     */
    public function getDataSourceCategoryIdAttribute($value)
    {
        return implode(',', $this->categories()->pluck('category_id')->toArray()) ?: $value;
    }
}
