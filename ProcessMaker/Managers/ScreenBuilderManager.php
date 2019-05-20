<?php
namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\ScreenType;

class ScreenBuilderManager
{
    private $javascriptRegistry;

    /**
     * Start our screen builder manager, creating an empty javascript registry
     *
     * @param $formType type of the form that is using the manager (DISPLAY, FORM, etc)
     */
    public function __construct()
    {
        $this->javascriptRegistry = [];
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

    public function addPackageScripts($type = 'DISPLAY')
    {
        // Depending of the form type we load the correct file with the components to display
        $extensionsFile = 'screen-builder-' . strtolower($type) . '-components.js';

        $directories = glob('vendor/processmaker/packages/*', GLOB_ONLYDIR);
        foreach($directories as $directory) {
            $extensionsFullName = $directory . '/js/' . $extensionsFile;
            $files = glob($extensionsFullName);
            if (count($files) > 0){
                $this->addScript('/' . $files[0]);
            }
        }
    }
}
