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
use ProcessMaker\Traits\TaskResourceIncludes;
use ProcessMaker\Traits\TaskScreenResourceTrait;

class TaskResource extends ApiResource
{
    use TaskResourceIncludes;

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
    ];

    protected static $defaultFields = [
        'id',
        'element_name',
        'element_id',
        'due_at',
    ];

    protected static $defaultFieldsFor = [
        'user' => ['id', 'firstname', 'lastname', 'email', 'username', 'avatar'],
        'requestor' => ['id', 'first_name', 'last_name', 'email'],
        'processRequest' => ['id', 'process_id', 'status'],
        'draft' => ['id', 'task_id', 'data'],
        'screen' => ['id', 'config'],
        'process' => ['id', 'name'],
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $array = [
            'id' => $this->id,
            'element_name' => $this->element_name,
            'element_id' => $this->element_id,
            'due_at' => $this->due_at,
            'advancedStatus' => $this->advanceStatus,
        ];

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
        $model->$relationship = $relationshipObject->select($relationshipColumns)->get();

        return true;
    }

    public static function preprocessInclude(Request $request, Builder $query): self
    {
        foreach (self::$defaultFields as $field) {
            $query->addSelect($field);
        }

        $include = $request->query('include', []);
        if ($include) {
            $include = explode(',', $include);
        }
        $include[] = 'process';
        $include[] = 'advanceStatus';

        if (in_array('data', $include)) {
            $query->addSelect('process_request_id');
        }

        foreach (self::$includeMethods as $key) {
            if (!in_array($key, $include)) {
                continue;
            }

            self::addRelationshipKeyColumn($query, $key);
        }

        $model = $query->first();
        foreach (self::$includeMethods as $key) {
            if (!in_array($key, $include)) {
                continue;
            }

            self::addRelationship($model, $key);
        }

        return new static($query->first());
    }

    private function processInclude(Request $request, array $array)
    {
        $include = $request->query('include', []);
        if ($include) {
            $include = explode(',', $include);
        }

        foreach (self::$includeMethods as $key) {
            if (!in_array($key, $include)) {
                continue;
            }

            $method = "include" . ucfirst($key);
            if (method_exists($this, $method)) {
                $attributes = $this->$method();
                $array = array_merge($array, $attributes);
            } else {
                $array[$key] = $this->$key;
            }
        }
        return $array;
    }
}
