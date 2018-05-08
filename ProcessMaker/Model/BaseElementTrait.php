<?php

namespace ProcessMaker\Model;

/**
 * Implements a method to create the shape of the element.
 *
 */
trait BaseElementTrait
{

    /**
     * Owner process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id', 'id');
    }

    /**
     * Shape of the flow node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function shape()
    {
        return $this->morphOne(Shape::class, 'shape', 'BOU_ELEMENT_TYPE', 'ELEMENT_UID');
    }

    /**
     * Create a shape for the element.
     *
     * @param \ProcessMaker\Model\Diagram $diagram
     * @param array $options
     *
     * @return $this
     */
    public function createShape(Diagram $diagram, array $options = [])
    {
        $shape = $diagram->createShape($this, $options);
        $this->shape()->save($shape);
        return $this;
    }
}
