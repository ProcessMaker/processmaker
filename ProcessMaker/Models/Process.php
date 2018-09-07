<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * Statuses:
     */
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'bpmn',
        'description',
        'name',
        'status',
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
     * @return array
     */
    public static function getRules()
    {
        return [
            'name' => 'required',
            'status' => 'in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE,
            'process_category_uuid' => 'nullable|exists:process_categories,uuid',
            'user_uuid' => 'exists:users,uuid',
        ];
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
}
