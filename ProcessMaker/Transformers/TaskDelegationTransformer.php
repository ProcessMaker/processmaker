<?php

namespace ProcessMaker\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use ProcessMaker\Application;
use ProcessMaker\Model\Delegation;
use League\Fractal\Resource\NullResource;

/**
 * Delegation transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class TaskDelegationTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'user',
        'task',
        'application',
    ];

    /**
     * Transform the activity.
     *
     * @param Delegation $item
     *
     * @return array
     */
    public function transform(Delegation $item)
    {
        $data = $item->toArray();
        unset($data['id'], $data['process_id'], $data['open_type'], $data['created_at'], $data['updated_at']);
        return $data;
    }

    /**
     * Fractal Include to add the fields of the users table in the transformation
     *
     * @param Delegation $item
     *
     * @return Item
     */
    public function includeUser(Delegation $item): Item
    {
        return $this->item($item->user, new UserTransformer());
    }

    /**
     * Fractal Include to add the fields of the users table in the transformation
     *
     * @param Delegation $item
     *
     * @return Item
     */
    public function includeTask(Delegation $item): Item
    {
        $definition = $item->definition;
        return $this->item($definition, function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name']
                ];
            });
    }

    /**
     * Fractal Include to add the fields of the Application table in the transformation
     *
     * @param Delegation $item
     *
     * @return item
     */
    public function includeApplication(Delegation $item): item
    {
        return $this->item($item->application, new ApplicationSingleTransformer());
    }
}
