<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Class Form
 *
 * @package ProcessMaker\Models
 *
 * @property string uuid
 * @property string title
 * @property string description
 * @property array content
 * @property string label
 * @property Carbon type
 *
 *   @OA\Schema(
 *   schema="formsEditable",
 *   @OA\Property(property="uuid", type="string", format="uuid"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="config", type="string"),
 * ),
 * @OA\Schema(
 *   schema="forms",
 *   allOf={@OA\Schema(ref="#/components/schemas/formsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class Form extends Model
{
    use HasBinaryUuid;

    protected $casts = [
        'config' => 'array'
    ];

    protected $fillable = [
        'title',
        'description',
        'config',
        'label',
        'type',
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
        $rules = [
            'title' => 'required|unique:forms,title',
        ];
        if ($existing) {
            // ignore the unique rule for this id
            $rules['title'] .= ',' . $existing->uuid . ',uuid';
        }
        return $rules;
    }
}
