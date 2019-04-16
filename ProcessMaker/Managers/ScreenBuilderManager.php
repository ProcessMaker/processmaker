<?php
namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Storage;

class ScreenBuilderManager
{
    private $javascriptRegistry;
    const SCREEN_BUILDER_EXTENSIONS_FILE = 'screen-builder-components.js';

    /**
     * Start our screen builder manager, creating an empty javascript registry
     */
    public function __construct()
    {
        $this->javascriptRegistry = [];
        $directories = glob('vendor/processmaker/packages/*', GLOB_ONLYDIR);
        foreach($directories as $directory) {
            $extensionsFile = $directory . '/js/' . ScreenBuilderManager::SCREEN_BUILDER_EXTENSIONS_FILE;
            $files = glob($extensionsFile);
            if (count($files) > 0){
                $this->addScript('/' . $files[0]);
            }
        }
    }

    /**
     * Add a new script to the modeler load.  These scripts can then interact with the modeler 
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
     * Retrieve the list of scripts that have been added. This is used in the modeler blade 
     * to execute each script in a script tag before the modeler is started.
     * 
     * @return array Collection of paths to scripts to load
     */
    public function getScripts()
    {
        return $this->javascriptRegistry;
    }
}