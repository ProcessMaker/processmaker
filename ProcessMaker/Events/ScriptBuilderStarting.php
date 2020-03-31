<?php
namespace ProcessMaker\Events;

use ProcessMaker\Managers\ScriptBuilderManager;

/**
 * Represents an event that the script builer is starting.
 * Any listeners can interact with the script builer manager to perform things such as 
 * script inclusion.
 */
class ScriptBuilderStarting
{
    public $manager;

    /**
     * Create a new event instance.
     * @param ScriptBuilderManager $manager
     *
     * @return void
     */
    public function __construct(ScriptBuilderManager $manager)
    {
        $this->manager = $manager;
    }

}
