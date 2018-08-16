<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Class OutputDocument
 * @package ProcessMaker\Model
 *
 * @property int id
 * @property string uid
 * @property string process_id
 * @property string title
 * @property string description
 * @property string filename
 * @property string template
 * @property string report_generator
 * @property string type
 * @property int versioning
 * @property int current_revision
 * @property string tags
 * @property int open_type
 * @property string generate
 * @property array properties
 *
 */
class OutputDocument extends Model
{
    use ValidatingTrait;
    use Uuid;

    /**
     * Values for report_generator
     */
    const DOC_REPORT_GENERATOR_TYPE = [
        'TCPDF',
        'HTML2PDF'
    ];

    /**
     * Values for generate
     */
    const DOC_GENERATE_TYPE = [
        'PDF',
        'WORD',
        'BOTH'
    ];

    /**
     * Values for type
     */
    const DOC_TYPE = [
        'HTML'
    ];


    /**
     * Values for pdf_security_permissions
     */
    const PDF_SECURITY_PERMISSIONS_TYPE = [
        'PRINT',
        'MODIFY',
        'COPY',
        'FORMS'
    ];

    protected $fillable = [
        'uid',
        'title',
        'description',
        'filename',
        'template',
        'report_generator',
        'type',
        'versioning',
        'current_revision',
        'tags',
        'open_type',
        'generate',
        'properties',
        'process_id',
    ];

    protected $rules = [
        'uid' => 'max:36',
        'process_id' => 'exists:processes,id',
        'filename' => 'required',
        'report_generator' => 'required',
        'type' => 'required',
        'current_revision' => 'required|min:0',
        'versioning' => 'required|min:0',
        'open_type' => 'required|boolean',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Accessor properties to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getPropertiesAttribute($value): ?array
    {
        $value = json_decode($value, true);
        $value['pdf_security_open_password'] = !empty($value['pdf_security_open_password']) ? decrypt($value['pdf_security_open_password']) : '';
        $value['pdf_security_owner_password'] = !empty($value['pdf_security_owner_password']) ? decrypt($value['pdf_security_owner_password']) : '';
        return $value;
    }

    /**
     * Mutator properties json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setPropertiesAttribute($value)
    {
        $value['pdf_security_open_password'] = !empty($value['pdf_security_open_password']) ? encrypt($value['pdf_security_open_password']) : '';
        $value['pdf_security_owner_password'] = !empty($value['pdf_security_owner_password']) ? encrypt($value['pdf_security_owner_password']) : '';
        $this->attributes['properties'] = empty($value) ? null : json_encode($value);
    }

}
