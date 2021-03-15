<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Collection;

class LoginManager
{
    private $addons;
    
    private $block;

    public function __construct()
    {
        $this->block = false;
        $this->addons = new Collection([]);
    }

    /**
     * Register addon
     *
     * @param $view string name of view file
     * @param $data array data to pass to view
     */
    public function add($view, $data = [])
    {
        $this->addons->push((object) [
            'view' => $view,
            'data' => $data,
        ]);
    }
    
    /**
     * Do not display standard login
     */
    public function block()
    {
        $this->block = true;
    }

    /**
     * Return block status
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * Get an addon by view name
     *
     * @param $view string name of view file
     */
    public function get($view)
    {
        return $this->addons->where('view', $view)->first();
    }

    /**
     * List addons
     *
     */
    public function list()
    {
        return $this->addons;
    }

}
