<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Support\Arr;
use ProcessMaker\Models\Screen;
use ProcessMaker\ProcessTranslations\ScreenTranslation;

class ScreenVersion extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $screenVersion = parent::toArray($request);

        $include = explode(',', $request->input('include', ''));

        $task = null;

        if (in_array('nested', $include)) {
            $this->setDefaultScreenForNestedScreens($screenVersion);
            $task = $request->route('task');
            $processRequest = null;
            if ($task) {
                $processRequest = $task->processRequest;
            }

            $nested = [];
            foreach ($this->parent->nestedScreenIds($processRequest) as $id) {
                $nestedScreen = Screen::find($id);
                if ($nestedScreen) {
                    $nested[] = $nestedScreen->versionFor($processRequest)->toArray();
                }
            }
            $screenVersion['nested'] = $nested;
        }

        // If web entry, apply translations
        if (!$task) {
            // Apply translations to screen
            $screenTranslation = new ScreenTranslation();
            $screenVersion['config'] = $screenTranslation->applyTranslations($screenVersion);
            // Apply translations to nested screens
            if (!array_key_exists('nested', $screenVersion)) {
                return $screenVersion;
            }
            foreach ($screenVersion['nested'] as &$nestedScreen) {
                $nestedScreen['config'] = $screenTranslation->applyTranslations($nestedScreen);
            }
        }

        return $screenVersion;
    }

    /**
     * Set the default screen for nested screens when no screen has been selected.
     */
    private function setDefaultScreenForNestedScreens(array &$screenVersion): void
    {
        $configArray = $screenVersion['config'];
        foreach ($configArray as $key => $config) {
            foreach ($config['items'] as $itemKey => $item) {
                if (isset($item['component']) && $item['component'] === 'FormNestedScreen') {
                    $configScreen = $item['config']['screen'] ?? null;
                    if (Screen::where('id', $configScreen)->doesntExist()) {
                        $defaultScreenId = Screen::where('key', 'default-form-screen')->value('id');
                        $path = "{$key}.items.{$itemKey}.config.screen";
                        Arr::set($configArray, $path, $defaultScreenId);
                    }
                }
            }
        }
        $screenVersion['config'] = $configArray;
    }
}
