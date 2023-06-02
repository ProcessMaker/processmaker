<?php

namespace ProcessMaker\Http\Resources;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\ProcessTranslations\ProcessTranslation;
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

        if (in_array('nested', $include)) {
            $task = $request->route('task');
            $processRequest = null;
            if ($task) {
                $processRequest = $task->processRequest;
            }

            $nested = [];
            foreach ($this->parent->nestedScreenIds($processRequest) as $id) {
                $nestedScreen = Screen::findOrFail($id);
                $nested[] = $nestedScreen->versionFor($processRequest)->toArray();
            }
            $screenVersion['nested'] = $nested;
        }

        // If web entry, apply translations
        if (!$task) {
            // Apply translations to screen
            $screenTranslation = new ScreenTranslation($screenVersion);
            $screenVersion['config'] = $screenTranslation->applyTranslations($screenVersion);
            // Apply translations to nested screens
            foreach ($screenVersion['nested'] as &$nestedScreen) {
                $nestedScreen['config'] = $screenTranslation->applyTranslations($nestedScreen);
            }
        }

        return $screenVersion;
    }
}
