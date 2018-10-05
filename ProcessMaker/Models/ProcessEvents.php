<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Collection;

/**
 * Process events relationship
 *
 */
class ProcessEvents extends Relation
{

    /**
     * @var Process[] $processModels
     */
    private $processModels = [];

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        //No Constraints
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->processModels = $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->parent->getStartEvents();
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        foreach ($models as $model) {
            $events = collect($model->getStartEvents());
            $model->setRelation($relation, $events);
        }
        return $models;
    }
}
