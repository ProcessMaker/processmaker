<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;

class MenuManager extends \Lavary\Menu\Menu
{
    /**
     * Create a new menu builder instance.
     *
     * @param string   $name
     * @param callable $callback
     * @param array $options (optional, it will be combined with the options to be applied)
     *
     * @return Builder
     */
    public function make($name, $callback, array $options = [])
    {
        if (!is_callable($callback)) {
            return null;
        }

        if (!array_key_exists($name, $this->menu)) {
            $this->menu[$name] = new MenuBuilder($name, array_merge($this->loadConf($name), $options));
        }

        // Registering the items
        call_user_func($callback, $this->menu[$name]);

        // Storing each menu instance in the collection
        $this->collection->put($name, $this->menu[$name]);

        // Make the instance available in all views
        View::share($name, $this->menu[$name]);

        // Dispatching the created event
        Event::dispatch("menu.created.{$name}");

        return $this->menu[$name];
    }
}
