<?php
namespace ProcessMaker\Managers;

class ScriptBuilderManager
{
    private $javascriptRegistry;

    /**
     * Start our script builder manager, registering our initial javascript 
     * which will add our intial node types to the script builder (base bpmn types/shapes)
     */
    public function __construct()
    {
        $this->javascriptRegistry = [];
    }

    /**
     * Add a new script to the script builder load.  These scripts can then interact with the script builder 
     * during it's startup lifecycle to do this such as register new node types.
     * 
     * @param string $script Path to the javascript to load
     * @return void
     */
    public function addScript($script)
    {
        $this->javascriptRegistry[] = $script;
    }

    /**
     * Retrieve the list of scripts that have been added. This is used in the script builder blade 
     * to execute each script in a script tag before the script builder is started.
     * 
     * @return array Collection of paths to scripts to load
     */
    public function getScripts()
    {
        return $this->javascriptRegistry;
    }
}
