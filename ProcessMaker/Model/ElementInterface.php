<?php

namespace ProcessMaker\Model;

/**
 * Any element that can appear in a Process.
 *
 */
interface ElementInterface
{

    /**
     * Owner process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function process();

    /**
     * Shape of the element.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function shape();

    /**
     * Shape of a BPMN element.
     *
     * @return Shape
     */
    public function createShape(Diagram $diagram, array $options = []);
}
