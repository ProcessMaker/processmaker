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
use ProcessMaker\Support\TaskDraftIncludes;
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

    /**
     * Strips or redacts draft on task index/show; parent::toArray() may serialize the relation first.
     */
    protected function sanitizeDraftInTaskArray(array $array, $request): array
    {
        $include = TaskDraftIncludes::parseIncludeList((string) $request->input('include', ''));

        $draftPayload = $this->resource->relationLoaded('draft')
            ? $this->draft
            : ($array['draft'] ?? null);

        if ($draftPayload !== null || array_key_exists('draft', $array)) {
            $array['draft'] = $this->resolveDraftPayloadForToken($this->resource, $draftPayload);
        } elseif (!TaskDraftIncludes::includesDraft($include)) {
            unset($array['draft']);
        }

        if (TaskDraftIncludes::includesDraft($include)) {
            $this->redactNestedDraftsInArray($array);
        }

        return $array;
    }

    /**
     * @return array<string, mixed>|TaskDraft|null
     */
    private function resolveDraftPayloadForToken(ProcessRequestToken $token, mixed $draft): array|TaskDraft|null
    {
        if ($draft === null) {
            return null;
        }

        if (!$draft instanceof TaskDraft) {
            if (!is_array($draft) || !isset($draft['id'], $draft['task_id'])) {
                return is_array($draft)
                    ? array_intersect_key($draft, array_flip(['id', 'task_id']))
                    : null;
            }
            $draft = (new TaskDraft())->setRawAttributes($draft, true);
            $draft->exists = true;
        }

        $user = Auth::user();

        if (!TaskDraft::draftsEnabled()) {
            if (!$user) {
                return null;
            }
            $this->ensureTokenAttributesForViewPolicy($token);
            if (!$this->userCanViewTaskForDraft($user, $token)) {
                return null;
            }
            $draft->delete();

            return null;
        }

        if (!$user) {
            return $draft->only(['id', 'task_id']);
        }

        $this->ensureTokenAttributesForViewPolicy($token);
        if (!$this->userCanViewTaskForDraft($user, $token)) {
            return $draft->only(['id', 'task_id']);
        }

        if (!array_key_exists('data', $draft->getAttributes())) {
            $draft = TaskDraft::query()->whereKey($draft->getKey())->first(['id', 'task_id', 'data']);
        }

        return $draft;
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function redactNestedDraftsInArray(array &$array): void
    {
        foreach ($array as &$value) {
            if (!is_array($value)) {
                continue;
            }
            if (array_is_list($value)) {
                foreach ($value as &$item) {
                    if (is_array($item)) {
                        $this->redactDraftOnSerializedToken($item);
                        $this->redactNestedDraftsInArray($item);
                    }
                }
                continue;
            }
            $this->redactDraftOnSerializedToken($value);
            $this->redactNestedDraftsInArray($value);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function redactDraftOnSerializedToken(array &$row): void
    {
        if (!isset($row['draft'], $row['id'])) {
            return;
        }

        $token = new ProcessRequestToken();
        $token->setRawAttributes([
            'id' => $row['id'],
            'user_id' => $row['user_id'] ?? null,
            'process_id' => $row['process_id'] ?? null,
            'process_request_id' => $row['process_request_id'] ?? null,
            'is_self_service' => $row['is_self_service'] ?? null,
        ], true);
        $token->exists = true;

        $row['draft'] = $this->resolveDraftPayloadForToken($token, $row['draft']);
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
     * Partial token projections (optimized index, fast show) may omit columns
     * required for draft visibility checks (assignee, self-service).
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
    }

    /** @see ProcessRequestTokenPolicy::view — avoids lazy-loading process on list rows */
    private function userCanViewTaskForDraft(User $user, ?ProcessRequestToken $token = null): bool
    {
        if ($user->is_administrator) {
            return true;
        }

        $token ??= $this->resource;
        if ((int) $token->user_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->id, $this->managerIdsForTaskProcess($token))) {
            return true;
        }

        return $user->canSelfServe($token);
    }

    /**
     * @return list<int|string>
     */
    private function managerIdsForTaskProcess(ProcessRequestToken $token): array
    {
        $process = $token->relationLoaded('process') ? $token->process : null;
        $processId = $process?->getKey() ?? $token->process_id;
        if (!$processId) {
            return [];
        }

        if ($process && array_key_exists('properties', $process->getAttributes())) {
            $properties = $this->decodeJsonColumn($process->getAttribute('properties'));
        } else {
            $properties = $this->decodeJsonColumn(
                Process::query()->whereKey($processId)->value('properties')
            );
        }

        $standIn = new Process();
        $standIn->setAttribute('properties', $properties);

        return $standIn->manager_id ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonColumn(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
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
