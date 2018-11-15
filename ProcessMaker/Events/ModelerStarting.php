<?php
namespace ProcessMaker\Events;

use ProcessMaker\Managers\ModelerManager;

/**
 * Represents an event that the modeler is starting.
 * Any listeners can interact with the modeler manager to perform things such as 
 * script inclusion.
 */
class ModelerStarting
{
    public $manager;

    /**
     * Create a new event instance.
     * @param ModelerManager $manager
     *
     * @return void
     */
    public function __construct(ModelerManager $manager)
    {
        $this->manager = $manager;
    }

}
