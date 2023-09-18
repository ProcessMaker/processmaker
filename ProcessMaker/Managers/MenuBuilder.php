<?php

namespace ProcessMaker\Managers;

use Lavary\Menu\Item;

class MenuBuilder extends \Lavary\Menu\Builder
{
    /**
     * Adds an item to the menu.
     *
     * @param string       $title
     * @param string|array $options
     *
     * @return Item $item
     */
    public function add($title, $options = '')
    {
        $id = isset($options['id']) ? $options['id'] : $this->id();

        $item = new Item($this, $id, $title, $options);

        if (!empty($options['beforeItem']) && !empty($options['index'])) {
            $beforeItem = $this->items->where('id', $options['beforeItem'])->first();
            $this->items->splice($this->items->search($beforeItem) - $options['index'], 0, [$item]);
        } elseif (!empty($options['afterItem']) && !empty($options['index'])) {
            $afterItem = $this->items->where('id', $options['afterItem'])->first();
            $this->items->splice($this->items->search($afterItem) + $options['index'], 0, [$item]);
        } else {
            $this->items->push($item);
        }

        return $item;
    }
}
