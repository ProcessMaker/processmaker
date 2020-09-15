<?php

namespace ProcessMaker;

use ProcessMaker\Exception\MaximumRecursionException;
use ProcessMaker\Models\Screen;

class ScreenConsolidator {
    private $screen;
    private $watchers = [];
    private $computed = [];
    private $custom_css = '';
    private $recursion = 0;

    public function __construct($screen)
    {
        $this->screen = $screen;
    }

    public function call()
    {
        if (is_array($this->screen->watchers)) {
            $this->watchers = $this->screen->watchers;
        }
        
        if (is_array($this->screen->computed)) {
            $this->computed = $this->screen->computed;
        }

        if ($this->screen->custom_css) {
            $this->custom_css = $this->screen->custom_css;
        }

        $config = $this->replace($this->screen->config);

        return [
            'config' => $config,
            'watchers' => $this->watchers,
            'custom_css' => $this->custom_css,
            'computed' => $this->computed,
        ];
    }

    public function replace($items)
    {
        $new = [];
        foreach ($items as $item) {
            if ($this->is('FormMultiColumn', $item)) {
                $new[] = $this->getMultiColumn($item, $new);

            } elseif ($this->is('FormNestedScreen', $item)) {
                $this->setNestedScreen($item, $new);

            } elseif ($this->hasItems($item)) {
                $new[] = $this->getWithItems($item);
            } else {
                $new[] = $item;
            }
        }
        return $new;
    }

    private function setNestedScreen($item, &$new)
    {
        if ($this->recursion > 10) {
            throw new MaximumRecursionException();
        }
        $this->recursion++;

        $screenId = $item['config']['screen'];
        $screen = Screen::findOrFail($screenId);

        $this->appendWatchers($screen);
        $this->appendComputed($screen);
        $this->appendCustomCss($screen);

        // Only use the first page
        foreach ($this->replace($screen->config[0]['items']) as $screenItem) {
            $new[] = $screenItem;
        }
        $this->recursion = 0;
    }

    private function is($component, $item) {
        return is_array($item) &&
               isset($item['component']) &&
               $item['component'] === $component;
    }

    private function hasItems($item) {
        return is_array($item) && isset($item['items']);
    }

    private function getMultiColumn($item)
    {
        $new = $item;
        $newItems = [];
        foreach ($item['items'] as $column) {
            $newItems[] = $this->replace($column);
        }
        $new['items'] = $newItems;
        return $new;
    }

    private function getWithItems($item)
    {
        $new = $item;
        $new['items'] = $this->replace($item['items']);
        return $new;
    }

    private function appendWatchers($screen)
    {
        if (!is_array($screen->watchers)) {
            return;
        }

        foreach ($screen->watchers as $watcher) {
            $this->watchers[] = $watcher;
        }
        
    }

    private function appendComputed($screen) 
    {
        if (!is_array($screen->computed)) {
            return;
        }

        $id = $this->computedMaxId();
        foreach ($screen->computed as $computed) {
            $id++;
            $computed['id'] = $id;
            $this->computed[] = $computed;
        }
    }

    private function appendCustomCss($screen)
    {
        if ($screen->custom_css) {
            $this->custom_css .= "\n" . $screen->custom_css;
        }
    }

    private function computedMaxId()
    {
        if (!$this->computed) {
            return 0;
        }
        return collect($this->computed)->max('id');
    }

}