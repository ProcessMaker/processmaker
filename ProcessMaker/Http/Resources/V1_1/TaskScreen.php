<?php

namespace ProcessMaker\Http\Resources\V1_1;

use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ScreenVersion as ScreenVersionResource;
use ProcessMaker\ProcessTranslations\ScreenTranslation;
use ProcessMaker\Traits\TaskScreenResourceTrait;

class TaskScreen extends ApiResource
{
    use TaskScreenResourceTrait;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->includeScreen($request);
    }

    private function includeScreen($request)
    {
        $array = ['screen' => null];

        $screen = $this->getScreenVersion();

        if ($screen) {
            if ($screen->type === 'ADVANCED') {
                $array['screen'] = $screen;
            } else {
                $resource = new ScreenVersionResource($screen);
                $array['screen'] = $resource->toArray($request);
            }
        } else {
            $array['screen'] = null;
        }

        if ($array['screen']) {
            // Apply translations to screen
            $screenTranslation = new ScreenTranslation();
            $array['screen']['config'] = $screenTranslation->applyTranslations($array['screen']);
            $array['screen']['config'] = $this->removeInspectorMetadata($array['screen']['config']);

            // Apply translations to nested screens
            if (array_key_exists('nested', $array['screen'])) {
                foreach ($array['screen']['nested'] as &$nestedScreen) {
                    $nestedScreen['config'] = $screenTranslation->applyTranslations($nestedScreen);
                    $nestedScreen['config'] = $this->removeInspectorMetadata($nestedScreen['config']);
                }
            }
        }

        return $array;
    }
}
