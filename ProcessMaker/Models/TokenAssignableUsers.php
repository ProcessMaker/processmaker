<?php
namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Collection;

/**
 * Process events relationship
 *
 */
class TokenAssignableUsers extends Relation
{

    /**
     * @var User[] $models
     */
    private $models = [];

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
        $this->models = $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        $ids = $this->parent->process->getAssignableUsers($this->element_id);
        return User::whereIn('id', $ids)->get();
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
            $ids = $model->process->getAssignableUsers($model->element_id);
            $children = collect(User::whereIn('id', $ids)->get());
            $model->setRelation($relation, $children);
        }
        return $models;
    }
}
