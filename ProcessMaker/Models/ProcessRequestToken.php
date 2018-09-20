<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Nayra\Bpmn\TokenTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * ProcessRequestToken is used to store the state of a token of the
 * Nayra engine
 *
 * @property string $uuid
 * @property string $process_request_uuid
 * @property string $user_uuid
 * @property string $element_uuid
 * @property string $element_type
 * @property string $status
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $due_at
 * @property \Carbon\Carbon $initiated_at
 * @property \Carbon\Carbon $riskchanges_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property ProcessRequest $request
 *
 */
class ProcessRequestToken extends Model implements TokenInterface
{
    use HasBinaryUuid;
    use TokenTrait;

    public $incrementing = false;

    /**
     * Attributes that are not mass assignable.
     *
     * @var array $guarded
     */
    protected $guarded = [
        'uuid',
        'updated_at',
        'created_at',
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
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $uuids = [
        'process_uuid',
        'process_request_uuid',
        'user_uuid',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];

    /**
     * Boot application as a process instance.
     *
     * @param array $argument
     */
    public function __construct(array $argument=[])
    {
        parent::__construct($argument);
        $this->bootElement([]);
        $this->setId(self::generateUuid());
    }

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'element_uuid' => 'required',
            'element_type' => 'required',
            'status' => 'required|in:ACTIVE,FAILING,COMPLETED,CLOSED,EVENT_CATCH',
            'process_request_uuid' => 'required|exists:process_requests,uuid',
        ];
    }

    /**
     * Get the process to which this version points to.
     *
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_uuid');
    }

    /**
     * Get the request of the token.
     *
     */
    public function processRequest()
    {
        return $this->belongsTo(ProcessRequest::class, 'process_request_uuid');
    }

    /**
     * Get the creator/author of this request.
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid');
    }

    /**
     * Get the BPMN definition of the element where the token is.
     *
     * @return array
     */
    public function getDefinition()
    {
        if (!$this->request) {
            return [];
        }
        $definitions = $this->request->process->getDefinitions();
        $element = $definitions->findElementById($this->element_uuid);
        if (!$element) {
            return [];
        }
        return $element->getBpmnElementInstance()->getProperties();
    }
}
