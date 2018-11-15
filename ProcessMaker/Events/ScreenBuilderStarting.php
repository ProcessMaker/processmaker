<?php
namespace ProcessMaker\Events;

use ProcessMaker\Managers\ScreenBuilderManager;

/**
 * Represents an event that the screen builder is starting.
 * Any listeners can interact with the builder manager to perform things such as 
 * script inclusion.
 */
class ScreenBuilderStarting
{
    public $manager;
    public $type;

    /**
     * Create a new event instance.
     * @param ScreenBuilderManager $manager
     * @param string $type The type of screen that is launching
     *
     * @return void
     */
    public function __construct(ScreenBuilderManager $manager, $type)
    {
        $this->manager = $manager;
        $this->type = $type;
    }

}
