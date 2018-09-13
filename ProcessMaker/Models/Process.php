<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents a business process definition.
 *
 * @property string $uuid
 * @property string $uuid_text
 * @property string $process_category_uuid
 * @property string $process_category_uuid_text
 * @property string $user_uuid
 * @property string $user_uuid_text
 * @property string $bpmn
 * @property string $description
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class Process extends Model
{

    use HasBinaryUuid;

    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'uuid',
        'user_uuid',
        'bpmn',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * BPMN data will be hidden. It will be able by its getter.
     *
     * @var array
     */
    protected $hidden = [
        'bpmn'
    ];

    /**
     * Parsed process BPMN definitions.
     *
     * @var \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    private $bpmnDefinitions;

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'process_category_uuid',
        'user_uuid',
    ];

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ProcessCategory::class, 'process_category_uuid');
    }

    /**
     * Validation rules.
     *
     * @param null $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $rules = [
            'name' => 'required|unique:processes,name',
            'description' => 'required',
            'status' => 'in:ACTIVE,INACTIVE',
            'process_category_uuid' => 'nullable|exists:process_categories,uuid',
            'user_uuid' => 'exists:users,uuid',
        ];

        if ($existing) {
            // ignore the unique rule for this id
            $rules['name'] .= ',' . $existing->uuid . ',uuid';
        }

        return $rules;
    }

    /**
     * Get the creator/author of this process.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid');
    }

    /**
     * Get the process definitions from BPMN field.
     *
     * @return ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    public function getDefinitions()
    {
        if (empty($this->bpmnDefinitions)) {
            $this->bpmnDefinitions = app(BpmnDocumentInterface::class, ['process' => $this]);
            if ($this->bpmn) {
                $this->bpmnDefinitions->loadXML($this->bpmn);
            }
        }
        return $this->bpmnDefinitions;
    }

    /**
     * Get the path of the process templates.
     *
     * @return string
     */
    public static function getProcessTemplatesPath()
    {
        return database_path('processes/templates');
    }

    public static function getProcessTemplate($name)
    {
        $path = self::getProcessTemplatesPath() . '/' . $name;
        //return Storage::disk('local')->get($path);
        return file_get_contents($path);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function requests()
    {
        return $this->hasMany(ProcessRequest::class);
    }

    /**
     * Category of the process.
     *
     * @return BelongsTo
     */
    public function collaborations()
    {
        return $this->hasMany(ProcessCollaboration::class);
    }
}
