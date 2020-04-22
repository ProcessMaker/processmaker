<?php
namespace ProcessMaker\Managers;

class GlobalScriptsManager
{
    private $javascriptRegistry = [];

    /**
     * Add a new script to the script builder load.  These scripts can then interact with the script builder 
     * during it's startup lifecycle to do this such as register new node types.
     * 
     * @param string $script Path to the javascript to load
     * @return void
     */
    public function addScript($script)
    {
        $time = filemtime(public_path($script));
        $this->javascriptRegistry[] = $script . ($time ? "?t=$time" : '');
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
