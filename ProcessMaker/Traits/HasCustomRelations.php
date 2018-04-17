<?php

namespace ProcessMaker\Traits;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use ProcessMaker\Relations\MorphToManyCustom;

trait HasCustomRelations
{
    use HasRelationships;

    /**
     * Overwrite column "_type" in polymorphic relationship
     *
     * @var string
     */
    protected $type = null;

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param  string $related
     * @param  string $name
     * @param  string $table
     * @param  string $foreignPivotKey
     * @param  string $relatedPivotKey
     * @param  string $parentKey
     * @param  string $relatedKey
     * @param  string $type
     *
     * @return MorphToMany
     */
    public function morphedByManyCustom($related, $name = null, $table = null, $foreignPivotKey = null,
                                        $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $type = null): MorphToMany
    {
        $this->type = $type;

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
        $relatedPivotKey = $relatedPivotKey ?: $name.'_id';

        $morphToMany = $this->morphToManyCustom(
            $related, $name, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, true
        );

        return $morphToMany;
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  bool  $inverse
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphToManyCustom($related, $name, $table = null, $foreignPivotKey = null,
                                $relatedPivotKey = null, $parentKey = null,
                                $relatedKey = null, $inverse = false): MorphToMany
    {
        $caller = $this->guessBelongsToManyRelation();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        $table = $table ?: Str::plural($name);

        return $this->newMorphToManyCustom(
            $instance->newQuery(), $this, $name, $table,
            $foreignPivotKey, $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $caller, $inverse
        );
    }


    /**
     * Instantiate a new HasManyThrough relationship with parameter $type.
     *
     * @param Builder $query
     * @param Model $parent
     * @param $name
     * @param $table
     * @param $foreignPivotKey
     * @param $relatedPivotKey
     * @param $parentKey
     * @param $relatedKey
     * @param null $relationName
     * @param bool $inverse
     *
     * @return MorphToManyCustom
     */
    protected function newMorphToManyCustom(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
                                      $relatedPivotKey, $parentKey, $relatedKey,
                                      $relationName = null, $inverse = false): MorphToManyCustom
    {
        return new MorphToManyCustom($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
            $relationName, $inverse, $this->type);
    }

}