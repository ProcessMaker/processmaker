<?php

namespace ProcessMaker\Managers;

class ModelerManager
{
    private $javascriptRegistry;
    private $javascriptParamsRegistry;

    /**
     * Start our modeler manager, registering our initial javascript
     * which will add our intial node types to the modeler (base bpmn types/shapes)
     */
    public function __construct()
    {
        $this->javascriptRegistry = [];
        // Include our default javascript for our core controls
        $this->addScript(mix('js/processes/modeler/initialLoad.js'));
    }

    /**
     * Add a script to the JavaScript registry with optional parameters.
     *
     * This method adds a script to the JavaScript registry along with optional parameters.
     * The script URL is added to the registry, and if additional parameters are provided,
     * they are merged with the default parameters.
     *
     * @param string $script The URL of the script to add to the registry.
     * @param array $params Additional parameters for configuring the script (optional).
     * @return void
     */
    public function addScript($script, array $params = [])
    {
        // Add the script URL to the JavaScript registry
        $this->javascriptRegistry[] = $script;

        // Merge additional parameters with the default parameters and add to the parameters registry
        $this->javascriptParamsRegistry[] = array_merge(['src' => $script], $params);
    }

    /**
     * Retrieve the list of scripts that have been added. This is used in the modeler blade
     * to execute each script in a script tag before the modeler is started.
     *
     * @return array Collection of paths to scripts to load
     */
    public function getScripts()
    {
        return $this->javascriptRegistry;
    }
    /**
     * Retrieve the JavaScript parameters registry.
     *
     * This method returns the JavaScript parameters registry, which contains
     * parameters used to configure scripts in the application.
     *
     * @return array The JavaScript parameters registry.
     */
    public function getScriptWithParams()
    {
        return $this->javascriptParamsRegistry;
    }
}
