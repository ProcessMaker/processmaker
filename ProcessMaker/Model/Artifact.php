<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Provides additional information about the process that is not directly
 * related to the flow.
 *
 * @property string $ART_UID
 * @property int $PRO_ID
 * @property string $ART_TYPE
 * @property string $ART_NAME
 * @property string $ART_CATEGORY_REF
 */
class Artifact extends Model implements ElementInterface
{

    use ValidatingTrait, InitializeUidTrait, BaseElementTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_ARTIFACT';
    protected $primaryKey = 'ART_UID';
    public $timestamps = false;
    public $incrementing = false;

    const TYPE = 'bpmnArtifact';
    const TYPE_HORIZONTAL_LINE = 'HORIZONTAL_LINE';
    const TYPE_VERTICAL_LINE = 'VERTICAL_LINE';
    const TYPE_TEXT_ANNOTATION = 'TEXT_ANNOTATION';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'ART_UID',
        'PRO_ID',
        'ART_TYPE',
        'ART_NAME',
        'ART_CATEGORY_REF'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'ART_UID'          => '',
        'PRO_ID'           => null,
        'ART_TYPE'         => null,
        'ART_NAME'         => null,
        'ART_CATEGORY_REF' => null
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'ART_UID'          => 'string',
        'PRO_ID'           => 'string',
        'ART_TYPE'         => 'string',
        'ART_NAME'         => 'string',
        'ART_CATEGORY_REF' => 'string'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'ART_TYPE'         => 'required|in:' . self::TYPE_HORIZONTAL_LINE . ',' . self::TYPE_VERTICAL_LINE . ',' . self::TYPE_TEXT_ANNOTATION,
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'ART_UID';
    }
}
