<?php

namespace ProcessMaker\Http\Resources\V1_1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ScreenVersion as ScreenVersionResource;
use ProcessMaker\Http\Resources\Task;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\ProcessTranslations\ProcessTranslation;
use ProcessMaker\Support\TaskDraftIncludes;
use ProcessMaker\Traits\TaskResourceIncludes;
use ProcessMaker\Traits\TaskScreenResourceTrait;

class TaskResource extends ApiResource
{
    use TaskResourceIncludes;

    const INCLUDE_PREFIX = 'include';

    protected static $includeMethods = [
        'data',
        'user',
        'requestor',
        'processRequest',
        'draft',
        'component',
        'screen',
        'requestData',
        'loopContext',
        'definition',
        'bpmnTagName',
        'interstitial',
        'userRequestPermission',
        'process',
        'elementDestination',
    ];

    protected static $defaultFields = [
        'id',
        'element_name',
        'element_id',
        'element_type',
        'status',
        'due_at',
        'process_request_id',
        'is_self_service',
        'token_properties',
    ];

    protected static $defaultIncludes = [
        'process',
        'advanceStatus',
    ];

    protected static $defaultFieldsFor = [
        'user' => [
            'id',
            'uuid',
            'email',
            'firstname',
            'lastname',
            'username',
            'status',
            'address',
            'city',
            'state',
            'postal',
            'country',
            'phone',
            'fax',
            'cell',
            'title',
            'birthdate',
            'timezone',
            'datetime_format',
            'language',
            'meta',
            'is_administrator',
            'expires_at',
            'loggedin_at',
            'created_at',
            'updated_at',
            'delegation_user_id',
            'manager_id',
            'schedule',
            'avatar',
        ],
        'requestor' => ['id', 'first_name', 'last_name', 'email'],
        'processRequest' => [
            'id',
            'uuid',
            'process_id',
            'user_id',
            'parent_request_id',
            'status',
            'do_not_sanitize',
            'errors',
            'completed_at',
            'initiated_at',
            'created_at',
            'updated_at',
            'case_title',
            'case_number',
            'callable_id',
            'process_version_id',
            'name',
        ],
        'draft' => ['id', 'task_id', 'data'],
        'screen' => ['id', 'config'],
        'process' => ['id', 'name'],
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        $array = [
            'advanceStatus' => $this->advanceStatus,
        ];
        foreach (self::$defaultFields as $field) {
            $array[$field] = $this->$field;
        }

        return $this->processInclude($request, $array);
    }

    private static function addRelationshipKeyColumn(Builder $query, string $relationship): bool
    {
        $model = $query->getModel();
        if (!method_exists($model, $relationship)) {
            return false;
        }
        $relationshipObject = $model->$relationship();
        if (!($relationshipObject instanceof Relation)) {
            return false;
        }

        if ($relationshipObject instanceof BelongsTo) {
            $relationshipKey = $relationshipObject->getForeignKeyName();
            $query->addSelect($relationshipKey);
        }

        return true;
    }

    private static function addRelationship(ProcessRequestToken $model, string $relationship): bool
    {
        if (!method_exists($model, $relationship)) {
            return false;
        }
        $relationshipObject = $model->$relationship();
        if (!($relationshipObject instanceof Relation)) {
            return false;
        }

        $relationshipColumns = self::$defaultFieldsFor[$relationship] ?? ['id'];
        $model->$relationship = $relationshipObject->select($relationshipColumns)->getResults();

        return true;
    }

    public static function preprocessInclude(Request $request, Builder $query): self
    {
        foreach (self::$defaultFields as $field) {
            $query->addSelect($field);
        }

        $parsed = self::parseV11RequestIncludes($request);
        $include = array_merge($parsed, self::$defaultIncludes);

        if (in_array('data', $include, true)) {
            $query->addSelect('process_request_id');
            self::$defaultFieldsFor['processRequest'][] = 'data';
        }

        foreach (self::$includeMethods as $key) {
            if (!in_array($key, $include, true)) {
                continue;
            }
            if ($key === 'draft' && !TaskDraftIncludes::includesRootDraft($parsed)) {
                continue;
            }

            self::addRelationshipKeyColumn($query, $key);
        }

        $model = $query->first();
        foreach (self::$includeMethods as $key) {
            if (!in_array($key, $include, true)) {
                continue;
            }
            if ($key === 'draft' && !TaskDraftIncludes::includesRootDraft($parsed)) {
                continue;
            }

            self::addRelationship($model, $key);
        }

        return new static($model);
    }

    /**
     * Build a resource for an already-loaded token (fast show path).
     */
    public static function fromTokenWithIncludes(ProcessRequestToken $token, Request $request): self
    {
        $parsed = self::parseV11RequestIncludes($request);
        $include = array_merge($parsed, self::$defaultIncludes);

        foreach (self::$includeMethods as $key) {
            if (!in_array($key, $include, true)) {
                continue;
            }
            if ($key === 'draft' && !TaskDraftIncludes::includesRootDraft($parsed)) {
                continue;
            }

            if (self::includeRelationshipAlreadyLoaded($token, $key)) {
                continue;
            }

            self::addRelationship($token, $key);
        }

        return new static($token);
    }

    /** @return list<string> */
    private static function parseV11RequestIncludes(Request $request): array
    {
        return TaskDraftIncludes::parseIncludeList((string) $request->input('include', ''));
    }

    private static function includeRelationshipAlreadyLoaded(ProcessRequestToken $token, string $key): bool
    {
        return match ($key) {
            'user' => $token->relationLoaded('user'),
            'processRequest' => $token->relationLoaded('processRequest'),
            'process' => $token->relationLoaded('process'),
            'draft' => $token->relationLoaded('draft'),
            'requestor' => $token->relationLoaded('processRequest')
                && $token->processRequest->relationLoaded('user'),
            default => false,
        };
    }

    private function processInclude(Request $request, array $array)
    {
        $include = self::parseV11RequestIncludes($request);

        foreach (self::$includeMethods as $key) {
            if ($key === 'draft' && TaskDraftIncludes::includesRootDraft($include)) {
                continue;
            }
            if (!in_array($key, $include, true)) {
                continue;
            }

            $method = self::INCLUDE_PREFIX . ucfirst($key);
            if (method_exists($this, $method)) {
                $attributes = $this->$method();
                $array = array_merge($array, $attributes);
            } else {
                $array[$key] = $this->$key;
            }
        }

        return $this->sanitizeDraftInTaskArray($array, $request);
    }
}
