<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Resources\ScreenVersion as ScreenVersionResource;
use ProcessMaker\Http\Resources\Users;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScreenVersion as ScreenVersionModel;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\ProcessTranslations\ScreenTranslation;
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
        $user = $this->processRequest->user;

        // Exclude 'active_at' to prevent ETag inconsistencies.
        if ($user) {
            $user->makeHidden(['active_at']);
        }

        return ['requestor' => new Users($user)];
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

            // Return null to prevent the frontend from trying to load
            // files from the deleted draft
            return ['draft' => null];
        }

        if (!$draft) {
            return ['draft' => null];
        }

        $user = Auth::user();
        if (!$user) {
            return ['draft' => $draft->only(['id', 'task_id'])];
        }

        $this->ensureTokenAttributesForViewPolicy($this->resource);
        if (!$user->can('view', $this->resource)) {
            return ['draft' => $draft->only(['id', 'task_id'])];
        }

        if (!array_key_exists('data', $draft->getAttributes())) {
            $draft = TaskDraft::query()->whereKey($draft->getKey())->first(['id', 'task_id', 'data']);
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
            $screenTranslation = new ScreenTranslation();
            $array['screen']['config'] = $screenTranslation->applyTranslations(new ScreenVersionModel($array['screen']));
            $array['screen']['config'] = $this->removeInspectorMetadata($array['screen']['config']);

            // Apply translations to nested screens
            if (array_key_exists('nested', $array['screen'])) {
                foreach ($array['screen']['nested'] as &$nestedScreen) {
                    $nestedScreen['config'] = $screenTranslation->applyTranslations(new ScreenVersionModel($nestedScreen));
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
        $definition = $this->getBpmnDefinition();

        return ['bpmn_tag_name' => $definition ? $definition->localName : null];
    }

    private function includeProcess()
    {
        return ['process' => $this->process];
    }

    public function includeInterstitial()
    {
        $interstitial = $this->getInterstitial();

        if ($interstitial['interstitial_screen'] && $interstitial['allow_interstitial']) {
            // Translate interstitials
            $screenTranslation = new ScreenTranslation();
            $translatedConf = $screenTranslation->applyTranslations($interstitial['interstitial_screen']->getLatestVersion());
            $interstitial['interstitial_screen']['config'] = $translatedConf;

            // Remove inspector metadata
            $interstitial['interstitial_screen']['config'] = $this->removeInspectorMetadata(
                $interstitial['interstitial_screen']['config'] ?: []
            );
        }

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

    /**
     * Include the element destination in the resource response.
     *
     * @return array
     */
    private function includeElementDestination()
    {
        $elementDestination = $this->resource->getElementDestinationAttribute();

        return ['elementDestination' => $elementDestination];
    }

    /**
     * Partial token/process projections (optimized index, fast show) may omit columns
     * required by ProcessRequestTokenPolicy::view before including draft payloads.
     */
    private function ensureTokenAttributesForViewPolicy(ProcessRequestToken $token): void
    {
        $tokenColumns = [];
        foreach (['user_id', 'process_id', 'is_self_service'] as $column) {
            if (!array_key_exists($column, $token->getAttributes())) {
                $tokenColumns[] = $column;
            }
        }

        if ($tokenColumns !== []) {
            $row = ProcessRequestToken::query()
                ->whereKey($token->getKey())
                ->first(array_merge(['id'], $tokenColumns));
            if ($row) {
                foreach ($tokenColumns as $column) {
                    $token->setAttribute($column, $row->{$column});
                }
            }
        }

        if ($token->is_self_service && !array_key_exists('self_service_groups', $token->getAttributes())) {
            $groups = ProcessRequestToken::query()->whereKey($token->getKey())->value('self_service_groups');
            $token->setAttribute('self_service_groups', $groups);
        }

        $process = $token->relationLoaded('process') ? $token->process : null;
        if ($process && !array_key_exists('manager_id', $process->getAttributes())) {
            $managerIds = Process::query()->whereKey($process->getKey())->value('manager_id');
            $process->setAttribute('manager_id', $managerIds);
        } elseif (!$token->relationLoaded('process') && $token->process_id) {
            $token->setRelation(
                'process',
                Process::query()->whereKey($token->process_id)->first(['id', 'manager_id'])
            );
        }
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
