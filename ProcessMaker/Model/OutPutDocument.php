<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Class OutPutDocument
 * @package ProcessMaker\Model
 *
 * @property int OUT_DOC_ID
 * @property string OUT_DOC_UID
 * @property string PRO_ID
 * @property string PRO_UID
 * @property string OUT_DOC_TITLE
 * @property string OUT_DOC_DESCRIPTION
 * @property string OUT_DOC_FILENAME
 * @property string OUT_DOC_TEMPLATE
 * @property string OUT_DOC_REPORT_GENERATOR
 * @property int OUT_DOC_LANDSCAPE
 * @property string OUT_DOC_MEDIA
 * @property int OUT_DOC_LEFT_MARGIN
 * @property int OUT_DOC_RIGHT_MARGIN
 * @property int OUT_DOC_TOP_MARGIN
 * @property int OUT_DOC_BOTTOM_MARGIN
 * @property string OUT_DOC_GENERATE
 * @property string OUT_DOC_TYPE
 * @property int OUT_DOC_CURRENT_REVISION
 * @property string OUT_DOC_FIELD_MAPPING
 * @property int OUT_DOC_VERSIONING
 * @property string OUT_DOC_DESTINATION_PATH
 * @property string OUT_DOC_TAGS
 * @property int OUT_DOC_PDF_SECURITY_ENABLED
 * @property string OUT_DOC_PDF_SECURITY_OPEN_PASSWORD
 * @property string OUT_DOC_PDF_SECURITY_OWNER_PASSWORD
 * @property array OUT_DOC_PDF_SECURITY_PERMISSIONS
 * @property int OUT_DOC_OPEN_TYPE
 *
 */
class OutPutDocument extends Model
{
    use ValidatingTrait;

    protected $table = 'OUTPUT_DOCUMENT';
    protected $primaryKey = 'OUT_DOC_ID';

    public $timestamps = false;

    /**
     * Values for OUT_DOC_REPORT_GENERATOR
     */
    const DOC_REPORT_GENERATOR_TYPE = [
        'TCPDF',
        'HTML2PDF'
    ];

    /**
     * Values for OUT_DOC_GENERATE
     */
    const DOC_GENERATE_TYPE = [
        'PDF',
        'WORD',
        'BOTH'
    ];

    /**
     * Values for OUT_DOC_TYPE
     */
    const DOC_TYPE = [
        'HTML'
    ];


    /**
     * Values for OUT_DOC_PDF_SECURITY_PERMISSIONS
     */
    const PDF_SECURITY_PERMISSIONS_TYPE = [
        'PRINT',
        'MODIFY',
        'COPY',
        'FORMS'
    ];

    protected $fillable = [
        'OUT_DOC_UID',
        'PRO_ID',
        'PRO_UID',
        'OUT_DOC_TITLE',
        'OUT_DOC_DESCRIPTION',
        'OUT_DOC_FILENAME',
        'OUT_DOC_TEMPLATE',
        'OUT_DOC_REPORT_GENERATOR',
        'OUT_DOC_LANDSCAPE',
        'OUT_DOC_MEDIA',
        'OUT_DOC_LEFT_MARGIN',
        'OUT_DOC_RIGHT_MARGIN',
        'OUT_DOC_TOP_MARGIN',
        'OUT_DOC_BOTTOM_MARGIN',
        'OUT_DOC_GENERATE',
        'OUT_DOC_TYPE',
        'OUT_DOC_CURRENT_REVISION',
        'OUT_DOC_FIELD_MAPPING',
        'OUT_DOC_VERSIONING',
        'OUT_DOC_DESTINATION_PATH',
        'OUT_DOC_TAGS',
        'OUT_DOC_PDF_SECURITY_ENABLED',
        'OUT_DOC_PDF_SECURITY_OPEN_PASSWORD',
        'OUT_DOC_PDF_SECURITY_OWNER_PASSWORD',
        'OUT_DOC_PDF_SECURITY_PERMISSIONS', '
        OUT_DOC_OPEN_TYPE'
    ];

    protected $attributes = [
        'OUT_DOC_UID' => null,
        'PRO_ID' => '',
        'PRO_UID' => null,
        'OUT_DOC_TITLE' => null,
        'OUT_DOC_DESCRIPTION' => null,
        'OUT_DOC_FILENAME' => null,
        'OUT_DOC_TEMPLATE' => null,
        'OUT_DOC_REPORT_GENERATOR' => 'HTML2PDF',
        'OUT_DOC_LANDSCAPE' => 0,
        'OUT_DOC_MEDIA' => 'letter',
        'OUT_DOC_LEFT_MARGIN' => 30,
        'OUT_DOC_RIGHT_MARGIN' => 15,
        'OUT_DOC_TOP_MARGIN' => 15,
        'OUT_DOC_BOTTOM_MARGIN' => 15,
        'OUT_DOC_GENERATE' => 'BOTH',
        'OUT_DOC_TYPE' => 'HTML',
        'OUT_DOC_CURRENT_REVISION' => 0,
        'OUT_DOC_FIELD_MAPPING' => null,
        'OUT_DOC_VERSIONING' => 0,
        'OUT_DOC_DESTINATION_PATH' => null,
        'OUT_DOC_TAGS' => null,
        'OUT_DOC_PDF_SECURITY_ENABLED' => 0,
        'OUT_DOC_PDF_SECURITY_OPEN_PASSWORD' => '',
        'OUT_DOC_PDF_SECURITY_OWNER_PASSWORD' => '',
        'OUT_DOC_PDF_SECURITY_PERMISSIONS' => '',
        'OUT_DOC_OPEN_TYPE' => 1
    ];

    protected $casts = [
        'OUT_DOC_UID' => 'string',
        'PRO_ID' => 'int',
        'PRO_UID' => 'string',
        'OUT_DOC_TITLE' => 'string',
        'OUT_DOC_DESCRIPTION' => 'string',
        'OUT_DOC_FILENAME' => 'string',
        'OUT_DOC_TEMPLATE' => 'string',
        'OUT_DOC_REPORT_GENERATOR' => 'string',
        'OUT_DOC_LANDSCAPE' => 'int',
        'OUT_DOC_MEDIA' => 'string',
        'OUT_DOC_LEFT_MARGIN' => 'int',
        'OUT_DOC_RIGHT_MARGIN' => 'int',
        'OUT_DOC_TOP_MARGIN' => 'int',
        'OUT_DOC_BOTTOM_MARGIN' => 'int',
        'OUT_DOC_GENERATE' => 'string',
        'OUT_DOC_TYPE' => 'string',
        'OUT_DOC_CURRENT_REVISION' => 'int',
        'OUT_DOC_FIELD_MAPPING' => 'string',
        'OUT_DOC_VERSIONING' => 'int',
        'OUT_DOC_DESTINATION_PATH' => 'string',
        'OUT_DOC_TAGS' => 'string',
        'OUT_DOC_PDF_SECURITY_ENABLED' => 'int',
        'OUT_DOC_PDF_SECURITY_OPEN_PASSWORD' => 'string',
        'OUT_DOC_PDF_SECURITY_OWNER_PASSWORD' => 'string',
        'OUT_DOC_PDF_SECURITY_PERMISSIONS' => 'array', '
        OUT_DOC_OPEN_TYPE' => 'int'
    ];

    protected $rules = [
        'OUT_DOC_UID' => 'required|max:32',
        'OUT_DOC_TITLE' => 'required|unique:OUTPUT_DOCUMENT,OUT_DOC_TITLE',
        'PRO_ID' => 'required',
        'OUT_DOC_DESCRIPTION' => 'required',
        'OUT_DOC_FILENAME' => 'required',
        'PRO_ID' => 'required',
        'PRO_UID' => 'required|max:32',
        'OUT_DOC_REPORT_GENERATOR' => 'required',
        'OUT_DOC_LANDSCAPE' => 'required|boolean',
        'OUT_DOC_MEDIA' => 'required',
        'OUT_DOC_LEFT_MARGIN' => 'required|min:0',
        'OUT_DOC_RIGHT_MARGIN' => 'required|min:0',
        'OUT_DOC_TOP_MARGIN' => 'required|min:0',
        'OUT_DOC_BOTTOM_MARGIN' => 'required|min:0',
        'OUT_DOC_TYPE' => 'required',
        'OUT_DOC_CURRENT_REVISION' => 'required|min:0',
        'OUT_DOC_VERSIONING' => 'required|min:0',
        'OUT_DOC_PDF_SECURITY_ENABLED' => 'required|boolean',
        'OUT_DOC_OPEN_TYPE' => 'required|boolean'
    ];

    protected $validationMessages = [
        'OUT_DOC_TITLE.unique' => 'A OutPut Document with the same name already exists in this process.'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'OUT_DOC_UID';
    }

    /**
     * Accessor OUT_DOC_PDF_SECURITY_PERMISSIONS to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getOutDocPdfSecurityPermissionsAttribute($value): ?array
    {
        return json_decode($value);
    }

    /**
     * Mutator OUT_DOC_PDF_SECURITY_PERMISSIONS json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setOutDocPdfSecurityPermissionsAttribute($value): void
    {
        $this->attributes['OUT_DOC_PDF_SECURITY_PERMISSIONS'] = empty($value) ? null : json_encode($value);
    }

    /**
     * Accessor OUT_DOC_PDF_SECURITY_OPEN_PASSWORD to json
     *
     * @param $value
     *
     * @return string
     */
    public function getOutDocPdfSecurityOpenPasswordAttribute($value): string
    {
        return !empty($value) ? decrypt($value) : '';
    }

    /**
     * Mutator OUT_DOC_PDF_SECURITY_OPEN_PASSWORD json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setOutDocPdfSecurityOpenPasswordAttribute($value): void
    {
        $this->attributes['OUT_DOC_PDF_SECURITY_OPEN_PASSWORD'] = !empty($value) ? encrypt($value) : '';
    }

    /**
     * Accessor OUT_DOC_PDF_SECURITY_OWNER_PASSWORD to json
     *
     * @param $value
     *
     * @return string
     */
    public function getOutDocPdfSecurityOwnerPasswordAttribute($value): string
    {
        return !empty($value) ? decrypt($value) : '';
    }

    /**
     * Mutator OUT_DOC_PDF_SECURITY_OWNER_PASSWORD json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setOutDocPdfSecurityOwnerPasswordAttribute($value): void
    {
        $this->attributes['OUT_DOC_PDF_SECURITY_OWNER_PASSWORD'] = !empty($value) ? encrypt($value) : '';
    }

}
