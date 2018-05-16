<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Class OutPutDocument
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
 * @property int landscape
 * @property string media
 * @property int left_margin
 * @property int right_margin
 * @property int top_margin
 * @property int bottom_margin
 * @property string generate
 * @property string type
 * @property int current_revision
 * @property string field_mapping
 * @property int versioning
 * @property string destination_path
 * @property string tags
 * @property int pdf_security_enabled
 * @property string pdf_security_open_password
 * @property string pdf_security_owner_password
 * @property array pdf_security_permissions
 * @property int open_type
 *
 */
class OutputDocument extends Model
{
    use ValidatingTrait;
    use Uuid;

    protected $table = 'output_documents';

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
        'process_id',
        'title',
        'description',
        'filename',
        'template',
        'report_generator',
        'landscape',
        'media',
        'left_margin',
        'right_margin',
        'top_margin',
        'bottom_margin',
        'generate',
        'type',
        'current_revision',
        'field_mapping',
        'versioning',
        'destination_path',
        'tags',
        'pdf_security_enabled',
        'pdf_security_open_password',
        'pdf_security_owner_password',
        'pdf_security_permissions',
        'open_type'
    ];

    protected $attributes = [
        'uid' => null,
        'process_id' => '',
        'title' => null,
        'description' => null,
        'filename' => null,
        'template' => null,
        'report_generator' => 'HTML2PDF',
        'landscape' => 0,
        'media' => 'letter',
        'left_margin' => 30,
        'right_margin' => 15,
        'top_margin' => 15,
        'bottom_margin' => 15,
        'generate' => 'BOTH',
        'type' => 'HTML',
        'current_revision' => 0,
        'field_mapping' => null,
        'versioning' => 0,
        'destination_path' => null,
        'tags' => null,
        'pdf_security_enabled' => 0,
        'pdf_security_open_password' => '',
        'pdf_security_owner_password' => '',
        'pdf_security_permissions' => '',
        'open_type' => 1
    ];

    protected $casts = [
        'uid' => 'string',
        'process_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'filename' => 'string',
        'template' => 'string',
        'report_generator' => 'string',
        'landscape' => 'int',
        'media' => 'string',
        'left_margin' => 'int',
        'right_margin' => 'int',
        'top_margin' => 'int',
        'bottom_margin' => 'int',
        'generate' => 'string',
        'type' => 'string',
        'current_revision' => 'int',
        'field_mapping' => 'string',
        'versioning' => 'int',
        'destination_path' => 'string',
        'tags' => 'string',
        'pdf_security_enabled' => 'int',
        'pdf_security_open_password' => 'string',
        'pdf_security_owner_password' => 'string',
        'pdf_security_permissions' => 'array',
        'open_type' => 'int'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'title' => 'required|unique:output_documents,title',
        'process_id' => 'exists:processes,id',
        'description' => 'required',
        'filename' => 'required',
        'report_generator' => 'required',
        'landscape' => 'required|boolean',
        'media' => 'required',
        'left_margin' => 'required|min:0',
        'right_margin' => 'required|min:0',
        'top_margin' => 'required|min:0',
        'bottom_margin' => 'required|min:0',
        'type' => 'required',
        'current_revision' => 'required|min:0',
        'versioning' => 'required|min:0',
        'pdf_security_enabled' => 'required|boolean',
        'open_type' => 'required|boolean'
    ];

    protected $validationMessages = [
        'title.unique' => 'A OutPut Document with the same name already exists in this process.'
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
     * Accessor pdf_security_permissions to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getPdfSecurityPermissionsAttribute($value): ?array
    {
        return json_decode($value);
    }

    /**
     * Mutator pdf_security_permissions json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setPdfSecurityPermissionsAttribute($value): void
    {
        $this->attributes['pdf_security_permissions'] = empty($value) ? null : json_encode($value);
    }

    /**
     * Accessor pdf_security_open_password to json
     *
     * @param $value
     *
     * @return string
     */
    public function getPdfSecurityOpenPasswordAttribute($value): string
    {
        return !empty($value) ? decrypt($value) : '';
    }

    /**
     * Mutator pdf_security_open_password json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setPdfSecurityOpenPasswordAttribute($value): void
    {
        $this->attributes['pdf_security_open_password'] = !empty($value) ? encrypt($value) : '';
    }

    /**
     * Accessor pdf_security_owner_password to json
     *
     * @param $value
     *
     * @return string
     */
    public function getPdfSecurityOwnerPasswordAttribute($value): string
    {
        return !empty($value) ? decrypt($value) : '';
    }

    /**
     * Mutator pdf_security_owner_password json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setPdfSecurityOwnerPasswordAttribute($value): void
    {
        $this->attributes['pdf_security_owner_password'] = !empty($value) ? encrypt($value) : '';
    }

}
