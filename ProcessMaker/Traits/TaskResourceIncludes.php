<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Resources\ScreenVersion as ScreenVersionResource;
use ProcessMaker\Http\Resources\Users;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\ProcessTranslations\ProcessTranslation;
use StdClass;

trait TaskResourceIncludes
{
    use TaskScreenResourceTrait;


    private function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }
        $dataManager = new DataManager();
        $task = $this->resource->loadTokenInstance();
        $this->loadedData = $dataManager->getData($task);

        return $this->loadedData;
    }

    private function includeData()
    {
        return ['data' => $this->getData()];
    }

    private function includeUser()
    {
        return ['user' => new Users($this->user)];
    }

    private function includeRequestor()
    {
        return ['requestor' => new Users($this->processRequest->user)];
    }

    private function includeProcessRequest()
    {
        return ['process_request' => $this->processRequest->attributesToArray()];
    }

    private function includeDraft()
    {
        $draft = $this->draft;
        if ($draft && !TaskDraft::draftsEnabled()) {
            // Drafts are used to get data from quick-fill to the screen,
            // but drafts are disabled so we need to delete it now that
            // it's been accessed.
            $draft->delete();
        }

        return ['draft' => $draft];
    }

    private function includeComponent()
    {
        $component = $this->getScreenVersion() ? $this->getScreenVersion()->parent->renderComponent() : null;

        return ['component' => $component];
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
            $processTranslation = new ProcessTranslation($this->process);
            $array['screen']['config'] = $processTranslation->applyTranslations($array['screen']);
            $array['screen']['config'] = $this->removeInspectorMetadata($array['screen']['config']);

            // Apply translations to nested screens
            if (array_key_exists('nested', $array['screen'])) {
                foreach ($array['screen']['nested'] as &$nestedScreen) {
                    $nestedScreen['config'] = $processTranslation->applyTranslations($nestedScreen);
                    $nestedScreen['config'] = $this->removeInspectorMetadata($nestedScreen['config']);
                }
            }
        }

        return $array;
    }

    private function includeRequestData()
    {
        $dataManager = new DataManager();
        $data = new StdClass();
        if ($this->processRequest->data) {
            $task = $this->resource->loadTokenInstance();
            $data = $dataManager->getData($task);
        }

        return ['request_data' => $data];
    }

    private function includeLoopContext()
    {
        return ['loop_context' => $this->getLoopContext()];
    }

    private function includeDefinition()
    {
        return ['definition' => $this->getDefinition()];
    }

    private function includeBpmnTagName()
    {
        return ['bpmn_tag_name' => $this->getBpmnDefinition()->localName];
    }

    private function includeProcess()
    {
        return ['process' => $this->process];
    }

    private function includeInterstitial()
    {
        $interstitial = $this->getInterstitial();

        // Translate interstitials
        $processTranslation = new ProcessTranslation($this->process);
        $translatedConf = $processTranslation->applyTranslations($interstitial['interstitial_screen']);
        $interstitial['interstitial_screen']['config'] = $translatedConf;

        // Remove inspector metadata
        $interstitial['interstitial_screen']['config'] = $this->removeInspectorMetadata(
            $interstitial['interstitial_screen']['config'] ?: []
        );

        return [
            'allow_interstitial' => $interstitial['allow_interstitial'],
            'interstitial_screen' => $interstitial['interstitial_screen'],
        ];
    }

    private function includeUserRequestPermission()
    {
        $userRequestPermission = $this->loadUserRequestPermission($this->processRequest, Auth::user(), []);

        return ['user_request_permission' => $userRequestPermission];
    }

    private function loadUserRequestPermission(ProcessRequest $request, User $user, array $permissions)
    {
        $permissions[] = [
            'process_request_id' => $request->id,
            'allowed' => $user ? $user->can('view', $request) : false,
        ];

        if ($request->parentRequest && $user) {
            $permissions = $this->loadUserRequestPermission($request->parentRequest, $user, $permissions);
        }

        return $permissions;
    }
}
