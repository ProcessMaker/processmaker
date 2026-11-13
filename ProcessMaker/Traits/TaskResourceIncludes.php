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

    /** @var array<int|string, ProcessRequestToken|null> */
    private array $draftPolicyTokenCache = [];

    /** @var array<int|string, list<int|string>> */
    private array $processManagerIdsCache = [];

    private const DRAFT_POLICY_TOKEN_COLUMNS = [
        'id',
        'user_id',
        'process_id',
        'process_request_id',
        'is_self_service',
        'self_service_groups',
    ];

    /** Do not treat case/request payload trees as task token rows. */
    private const DRAFT_REDACT_EXCLUDED_KEYS = [
        'request_data',
        'data',
        'screen',
        'config',
        'nested',
        'definition',
        'interstitial_screen',
        'user_request_permission',
        'elementDestination',
        'loop_context',
    ];

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

        if (!TaskDraftIncludes::includesDraft($include)) {
            unset($array['draft']);
            if ($this->arrayContainsNestedTokenDrafts($array)) {
                $this->prefetchDraftPolicyTokensForNestedArray($array);
                $this->redactNestedDraftsInArray($array);
            }

            return $array;
        }

        if (TaskDraftIncludes::includesRootDraft($include)) {
            $draftPayload = $this->resource->relationLoaded('draft')
                ? $this->draft
                : ($array['draft'] ?? null);

            $array['draft'] = $this->resolveDraftPayloadForToken($this->resource, $draftPayload, true);
        } else {
            unset($array['draft']);
        }

        if (TaskDraftIncludes::includesNestedDraft($include)) {
            $this->prefetchDraftPolicyTokensForNestedArray($array);
            $this->redactNestedDraftsInArray($array);
        }

        return $array;
    }

    /**
     * @return array<string, mixed>|TaskDraft|null
     */
    private function resolveDraftPayloadForToken(
        ProcessRequestToken $token,
        mixed $draft,
        bool $consumeDraftWhenDisabled = false,
    ): array|TaskDraft|null {
        $token = $this->persistedTokenForDraftPolicy($token);
        $draft = $token !== null ? $this->taskDraftForToken($token, $draft) : null;
        if ($token === null || $draft === null) {
            return null;
        }

        if (!TaskDraft::draftsEnabled()) {
            return $this->resolveDraftPayloadWhenDraftsDisabled(
                Auth::user(),
                $token,
                $draft,
                $consumeDraftWhenDisabled
            );
        }

        return $this->resolveDraftPayloadWhenDraftsEnabled(Auth::user(), $token, $draft);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveDraftPayloadWhenDraftsDisabled(
        ?User $user,
        ProcessRequestToken $token,
        TaskDraft $draft,
        bool $consumeDraftWhenDisabled,
    ): ?array {
        if (!$user || !$this->callerCanViewTaskForDraft($user, $token)) {
            return null;
        }

        if ($consumeDraftWhenDisabled
            && $user
            && (int) $user->id === (int) $token->user_id) {
            $draft->delete();
        }

        return null;
    }

    /**
     * @return array<string, mixed>|TaskDraft|null
     */
    private function resolveDraftPayloadWhenDraftsEnabled(?User $user, ProcessRequestToken $token, TaskDraft $draft): array|TaskDraft|null
    {
        if (!$user || !$this->callerCanViewTaskForDraft($user, $token)) {
            return $draft->only(['id', 'task_id']);
        }

        if (!array_key_exists('data', $draft->getAttributes())) {
            $data = TaskDraft::query()
                ->whereKey($draft->getKey())
                ->where('task_id', $token->id)
                ->value('data');
            if ($data === null) {
                return null;
            }
            $draft->setAttribute('data', $data);
        }

        return $draft;
    }

    private function callerCanViewTaskForDraft(User $user, ProcessRequestToken $token): bool
    {
        $this->ensureTokenAttributesForViewPolicy($token);

        return $this->userCanViewTaskForDraft($user, $token);
    }

    private function persistedTokenForDraftPolicy(ProcessRequestToken $candidate): ?ProcessRequestToken
    {
        $tokenId = $candidate->getKey();
        if ($tokenId === null || $tokenId === '') {
            return null;
        }

        if ($this->resource instanceof ProcessRequestToken
            && (int) $this->resource->getKey() === (int) $tokenId
            && $this->tokenHasDraftPolicyAttributes($this->resource)) {
            return $this->resource;
        }

        if (!array_key_exists($tokenId, $this->draftPolicyTokenCache)) {
            $this->draftPolicyTokenCache[$tokenId] = ProcessRequestToken::query()
                ->whereKey($tokenId)
                ->first(self::DRAFT_POLICY_TOKEN_COLUMNS);
        }

        return $this->draftPolicyTokenCache[$tokenId];
    }

    private function tokenHasDraftPolicyAttributes(ProcessRequestToken $token): bool
    {
        $attrs = $token->getAttributes();
        foreach (['user_id', 'process_id', 'is_self_service'] as $column) {
            if (!array_key_exists($column, $attrs)) {
                return false;
            }
        }

        if ($token->is_self_service && !array_key_exists('self_service_groups', $attrs)) {
            return false;
        }

        return true;
    }

    /**
     * Resolve draft only when task_id matches the persisted token id (never trust nested JSON).
     */
    private function taskDraftForToken(ProcessRequestToken $token, mixed $draft): ?TaskDraft
    {
        $resolved = null;

        if ($draft instanceof TaskDraft) {
            if ((int) $draft->task_id === (int) $token->id) {
                $resolved = $draft;
            }
        } elseif ($draft === null) {
            $resolved = TaskDraft::query()
                ->where('task_id', $token->id)
                ->first(['id', 'task_id', 'data']);
        } else {
            $draftId = $this->draftIdFromPayload($draft);
            if ($draftId !== null) {
                $resolved = TaskDraft::query()
                    ->whereKey($draftId)
                    ->where('task_id', $token->id)
                    ->first(['id', 'task_id', 'data']);
            }
        }

        return $resolved;
    }

    private function draftIdFromPayload(mixed $draft): int|string|null
    {
        if ($draft instanceof TaskDraft) {
            return $draft->getKey();
        }

        if (is_array($draft)) {
            return $draft['id'] ?? null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function redactNestedDraftsInArray(array &$array): void
    {
        foreach ($array as $key => &$value) {
            if (!is_array($value) || in_array($key, self::DRAFT_REDACT_EXCLUDED_KEYS, true)) {
                continue;
            }

            if ($key === 'tokens' && array_is_list($value)) {
                foreach ($value as &$item) {
                    if (is_array($item)) {
                        $this->redactDraftOnSerializedToken($item);
                    }
                }
                continue;
            }

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

        $token = $this->tokenFromNestedTokenRow($row);
        if (!$token) {
            unset($row['draft']);

            return;
        }

        $row['draft'] = $this->resolveDraftPayloadForToken($token, $row['draft']);
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function prefetchDraftPolicyTokensForNestedArray(array $array): void
    {
        $ids = [];
        $this->collectNestedTokenIdsWithDraft($array, $ids);
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return;
        }

        $toLoad = [];
        foreach ($ids as $id) {
            if (array_key_exists($id, $this->draftPolicyTokenCache)) {
                continue;
            }
            $toLoad[] = $id;
        }

        if ($toLoad === []) {
            return;
        }

        $rows = ProcessRequestToken::query()
            ->whereIn('id', $toLoad)
            ->get(self::DRAFT_POLICY_TOKEN_COLUMNS);

        foreach ($rows as $row) {
            $this->draftPolicyTokenCache[$row->getKey()] = $row;
        }

        foreach ($toLoad as $id) {
            if (!array_key_exists($id, $this->draftPolicyTokenCache)) {
                $this->draftPolicyTokenCache[$id] = null;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $array
     * @param  list<int|string>  $ids
     */
    private function collectNestedTokenIdsWithDraft(array $array, array &$ids): void
    {
        foreach ($array as $key => $value) {
            if (!is_array($value) || in_array($key, self::DRAFT_REDACT_EXCLUDED_KEYS, true)) {
                continue;
            }

            if ($key === 'tokens' && array_is_list($value)) {
                foreach ($value as $item) {
                    if (is_array($item) && isset($item['draft'], $item['id'])) {
                        $ids[] = $item['id'];
                    }
                }
                continue;
            }

            $this->collectNestedTokenIdsWithDraft($value, $ids);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function tokenFromNestedTokenRow(array $row): ?ProcessRequestToken
    {
        if (!isset($row['id'])) {
            return null;
        }

        $candidate = new ProcessRequestToken();
        $candidate->exists = true;
        $candidate->setAttribute('id', $row['id']);

        return $this->persistedTokenForDraftPolicy($candidate);
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function arrayContainsNestedTokenDrafts(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (!$this->isNestedDraftTraversalCandidate($key, $value)) {
                continue;
            }

            if ($this->nestedTokenDraftsPresentAtKey($key, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function nestedTokenDraftsPresentAtKey(string|int $key, array $value): bool
    {
        if ($key === 'tokens' && array_is_list($value)) {
            return $this->serializedTokenListContainsDraft($value);
        }

        return $this->arrayContainsNestedTokenDrafts($value);
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function serializedTokenListContainsDraft(array $tokens): bool
    {
        foreach ($tokens as $item) {
            if (is_array($item) && array_key_exists('draft', $item)) {
                return true;
            }
        }

        return false;
    }

    private function isNestedDraftTraversalCandidate(mixed $key, mixed $value): bool
    {
        return is_array($value) && !in_array($key, self::DRAFT_REDACT_EXCLUDED_KEYS, true);
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
        if ($this->tokenDraftPolicyAttributesAreAuthoritative($token)) {
            return;
        }

        $tokenId = $token->getKey();
        $cached = $tokenId !== null ? ($this->draftPolicyTokenCache[$tokenId] ?? null) : null;
        if ($cached instanceof ProcessRequestToken && $this->tokenHasDraftPolicyAttributes($cached)) {
            $this->mergeDraftPolicyAttributes($token, $cached);

            return;
        }

        $columns = ['id', 'user_id', 'process_id', 'is_self_service'];
        $needsGroups = (bool) $token->is_self_service
            && !array_key_exists('self_service_groups', $token->getAttributes());
        if ($needsGroups) {
            $columns[] = 'self_service_groups';
        }

        $row = ProcessRequestToken::query()->whereKey($tokenId)->first($columns);
        if ($row) {
            $this->mergeDraftPolicyAttributes($token, $row);
            if ($tokenId !== null) {
                $this->draftPolicyTokenCache[$tokenId] = $row;
            }
        }
    }

    private function mergeDraftPolicyAttributes(ProcessRequestToken $token, ProcessRequestToken $source): void
    {
        foreach (['user_id', 'process_id', 'is_self_service', 'self_service_groups'] as $column) {
            if (array_key_exists($column, $source->getAttributes())) {
                $token->setAttribute($column, $source->getAttribute($column));
            }
        }
    }

    /**
     * Serialized nested token rows must not satisfy policy checks without a DB row.
     */
    private function tokenDraftPolicyAttributesAreAuthoritative(ProcessRequestToken $token): bool
    {
        $tokenId = $token->getKey();
        if (!$this->tokenHasDraftPolicyAttributes($token) || $tokenId === null || $tokenId === '') {
            return false;
        }

        if ($this->resource instanceof ProcessRequestToken
            && (int) $this->resource->getKey() === (int) $tokenId
            && $this->tokenHasDraftPolicyAttributes($this->resource)) {
            return true;
        }

        $cached = $this->draftPolicyTokenCache[$tokenId] ?? null;

        return $cached instanceof ProcessRequestToken && $cached === $token;
    }

    /** @see ProcessRequestTokenPolicy::view — avoids lazy-loading process on list rows */
    private function userCanViewTaskForDraft(User $user, ?ProcessRequestToken $token = null): bool
    {
        if ($user->is_administrator) {
            return true;
        }

        $token ??= $this->resource;

        return (int) $token->user_id === (int) $user->id
            || in_array($user->id, $this->managerIdsForTaskProcess($token))
            || $user->canSelfServe($token);
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

        if (array_key_exists($processId, $this->processManagerIdsCache)) {
            return $this->processManagerIdsCache[$processId];
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

        $managerIds = $standIn->manager_id ?? [];
        $this->processManagerIdsCache[$processId] = $managerIds;

        return $managerIds;
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
