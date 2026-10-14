<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Mcp\McpSupport;
use ProcessMaker\Mcp\Platform\PlatformWriter;
use ProcessMaker\Mcp\Platform\ScreenCreationResult;
use ProcessMaker\Mcp\Process\ScreenBuilder;
use ProcessMaker\Mcp\Process\ScreenConfigNormalizer;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use ProcessMaker\Plugins\Collections\Models\Collection;
use ProcessMaker\Providers\WorkflowServiceProvider;

class Writer
{
    private ?PlatformWriter $platformWriter = null;

    public function createProcess(array $data): Process
    {
        if (empty($data['process_category_id'])) {
            $data['process_category_id'] = $this->defaultProcessCategoryId();
        }

        $process = $this->platform()->createProcess($data);

        return $this->ensureProcessIsStartable($process);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProcess(string $processId, array $data): Process
    {
        $process = Process::findOrFail($processId);

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? null,
            'process_category_id' => $data['process_category_id'] ?? null,
        ], fn ($value) => $value !== null);

        if ($payload === []) {
            return $process;
        }

        return $this->platform()->updateProcess($process, $payload);
    }

    public function createScreen(array $data): Screen
    {
        return $this->createScreenResult($data)->screen;
    }

    public function createScreenResult(array $data): ScreenCreationResult
    {
        $config = $this->parseConfigJson($data['config_json'] ?? $data['config'] ?? []);
        $config = app(ScreenConfigNormalizer::class)->normalize($config);

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? $data['title'],
            'type' => $data['type'] ?? 'FORM',
            'config_json' => $config,
            'computed' => $data['computed'] ?? [],
            'watchers' => $data['watchers'] ?? [],
            'custom_css' => $data['custom_css'] ?? '',
            'screen_category_id' => $data['screen_category_id'] ?? $this->defaultScreenCategoryId(),
        ];

        return $this->platform()->createScreenResult($payload);
    }

    /**
     * Assign default category and start permissions so MCP processes appear in Launchpad.
     */
    public function ensureProcessIsStartable(Process $process, ?int $userId = null): Process
    {
        $userId ??= Auth::id() ?? User::firstOrFail()->id;

        if ($process->categories()->count() === 0) {
            $process->process_category_id = (string) $this->defaultProcessCategoryId();
            $process->saveOrFail();
        }

        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $changed = false;

        foreach ($xpath->query('//bpmn:startEvent[@id]') as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $assignment = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
            if ($assignment !== '') {
                continue;
            }

            $this->setPmAttributeOnNode($node, 'assignment', 'user');
            $this->setPmAttributeOnNode($node, 'assignedUsers', (string) $userId);
            $this->setPmAttributeOnNode($node, 'assignedGroups', '');
            $changed = true;
        }

        if ($changed) {
            $this->platform()->persistProcessBpmn($process, $definitions->saveXML());
            $process->refresh();
        }

        return $process;
    }

    /**
     * @return array<string, mixed>
     */
    public function screenCreationPayload(ScreenCreationResult $result): array
    {
        $screen = $result->screen->fresh() ?? $result->screen;
        $validation = app(ScreenBuilder::class)->validateScreenConfig($screen->config ?? []);
        $firstItem = $screen->config[0]['items'][0] ?? null;

        return [
            'id' => $screen->id,
            'title' => $screen->title,
            'type' => $screen->type,
            'import_method' => $result->importMethod,
            'warnings' => $result->warnings,
            'config_valid' => $validation['valid'],
            'config_issues' => $validation['issues'],
            'editor_ready' => is_array($firstItem)
                && is_string($firstItem['uuid'] ?? null)
                && ($firstItem['uuid'] ?? '') !== ''
                && is_array($firstItem['inspector'] ?? null)
                && ($firstItem['inspector'] ?? []) !== [],
            'mcp_version' => McpSupport::MCP_VERSION,
        ];
    }

    public function createScript(array $data): Script
    {
        $user = Auth::user() ?? User::firstOrFail();

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? $data['title'],
            'code' => $data['code'],
            'language' => $data['language'] ?? 'php',
            'run_as_user_id' => $data['run_as_user_id'] ?? $user->id,
            'script_category_id' => $data['script_category_id'] ?? $this->defaultScriptCategoryId(),
        ];

        return $this->platform()->createScript($payload);
    }

    public function updateProcessBpmn(string $processId, string $bpmn): Process
    {
        $process = Process::findOrFail($processId);

        return $this->platform()->updateProcessBpmn($process, $bpmn);
    }

    public function linkScreenToTask(string $processId, string $elementId, string $screenId): Process
    {
        Screen::findOrFail($screenId);

        return $this->setBpmnElementAttribute($processId, $elementId, 'screenRef', $screenId);
    }

    public function linkScriptToTask(string $processId, string $elementId, string $scriptId): Process
    {
        Script::findOrFail($scriptId);

        return $this->setBpmnElementAttribute($processId, $elementId, 'scriptRef', $scriptId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function autoLinkMigrationAssets(string $processId, array $payload): array
    {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);

        $screensByPm3 = $this->indexAssetsByPm3($payload['screens'] ?? []);
        $scriptsByPm3 = $this->indexAssetsByPm3($payload['scripts'] ?? []);

        $taskElementMap = MigrationBpmn::buildTaskElementMap($payload, $xpath);
        $linkedScreens = [];
        $linkedScripts = [];
        $warnings = [];
        $modified = false;

        foreach ($payload['steps'] ?? [] as $step) {
            if (!is_array($step) || ($step['step_type'] ?? null) !== 'DYNAFORM') {
                continue;
            }

            $dynUid = $step['dyn_uid'] ?? null;
            $tasUid = $step['tas_uid'] ?? null;
            $screenId = is_string($dynUid) ? ($screensByPm3[$dynUid] ?? null) : null;

            if ($screenId === null || !is_string($tasUid)) {
                continue;
            }

            $elementId = $taskElementMap[$tasUid] ?? null;
            if ($elementId === null || $this->findBpmnTaskNode($xpath, $elementId) === null) {
                $warnings[] = "No BPMN task found for PM3 task {$tasUid}.";
                continue;
            }

            $this->setPmAttribute($xpath, $elementId, 'screenRef', $screenId);
            $linkedScreens[] = [
                'tas_uid' => $tasUid,
                'dyn_uid' => $dynUid,
                'element_id' => $elementId,
                'screen_id' => $screenId,
            ];
            $modified = true;
        }

        foreach ($payload['script_tasks'] ?? [] as $scriptTask) {
            if (!is_array($scriptTask)) {
                continue;
            }

            $actUid = $scriptTask['act_uid'] ?? null;
            $triUid = $scriptTask['tri_uid'] ?? null;
            $scriptId = is_string($triUid) ? ($scriptsByPm3[$triUid] ?? null) : null;

            if ($scriptId === null || !is_string($actUid)) {
                continue;
            }

            $elementId = $taskElementMap[$actUid] ?? $actUid;
            if ($xpath->query("//bpmn:scriptTask[@id='{$elementId}']")->item(0) === null) {
                $warnings[] = "No BPMN scriptTask found for activity {$actUid}.";
                continue;
            }

            $this->setPmAttribute($xpath, $elementId, 'scriptRef', $scriptId);
            $linkedScripts[] = [
                'act_uid' => $actUid,
                'tri_uid' => $triUid,
                'element_id' => $elementId,
                'script_id' => $scriptId,
            ];
            $modified = true;
        }

        if ($modified) {
            $this->saveProcessBpmn($process, $definitions);
        }

        return [
            'process_id' => $process->id,
            'linked_screens' => $linkedScreens,
            'linked_scripts' => $linkedScripts,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $dynaform
     * @param  array<int, mixed>  $stylesheets
     * @return array{screen: Screen, warnings: array<int, string>, nested_screens: array<int, array<string, mixed>>}
     */
    public function createScreenFromDynaform(array $dynaform, array $stylesheets = []): array
    {
        $converter = new DynaformConverter();
        $converted = $converter->toScreenConfig($dynaform);
        $title = $dynaform['DYN_TITLE'] ?? 'Migrated Screen';
        $warnings = $converted['warnings'];
        $config = $converted['config'];
        $mainItems = $config[0]['items'] ?? [];
        $nestedScreens = [];

        foreach ($converted['grid_definitions'] as $gridField) {
            $gridLabel = $gridField['label'] ?? $gridField['variable'] ?? 'Grid';
            $columns = $converter->convertGridColumns($gridField);
            $warnings = array_merge($warnings, $columns['warnings']);

            $nestedResult = $this->createScreenResult([
                'title' => $title . ' - ' . $gridLabel,
                'description' => 'Grid columns migrated from PM3 dynaform',
                'config_json' => [[
                    'name' => 'data',
                    'items' => $columns['items'],
                ]],
            ]);
            $warnings = array_merge($warnings, $nestedResult->warnings);
            $nestedScreen = $nestedResult->screen;

            $nestedScreens[] = [
                'pm4_id' => $nestedScreen->id,
                'title' => $nestedScreen->title,
                'grid_variable' => $gridField['variable'] ?? $gridField['name'] ?? null,
            ];

            $mainItems[] = $converter->buildRecordListItem($gridField, (string) $nestedScreen->id, $columns);
        }

        $config[0]['items'] = $mainItems;

        $screenResult = $this->createScreenResult([
            'title' => $title,
            'description' => $dynaform['DYN_DESCRIPTION'] ?? $title,
            'config_json' => $config,
        ]);
        $warnings = array_merge($warnings, $screenResult->warnings);
        $screen = $screenResult->screen;

        $customCss = $this->mergeCustomCss(
            $converted['custom_css'],
            $stylesheets,
            $converted['field_registry'] ?? []
        );

        if ($customCss !== null && $customCss !== '') {
            $screen->custom_css = $customCss;
            $screen->saveOrFail();
        }

        return [
            'screen' => $screen,
            'warnings' => $warnings,
            'nested_screens' => $nestedScreens,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applyMigrationBundle(array $bundle, bool $resume = false, bool $fresh = false): array
    {
        $proUid = $this->resolveBundleProUid($bundle);
        $existingLog = MigrationLog::load($proUid);

        if ($fresh && $existingLog !== null) {
            $existingLog->delete();
            $existingLog = null;
        }

        if ($resume) {
            if ($existingLog === null || !$existingLog->canResume()) {
                throw ValidationException::withMessages([
                    'resume' => "No resumable migration log found for pro_uid {$proUid}.",
                ]);
            }

            return $this->runMigrationBundle($bundle, $existingLog);
        }

        if ($existingLog !== null) {
            if ($existingLog->getStatus() === 'completed') {
                throw ValidationException::withMessages([
                    'bundle' => "Migration for {$proUid} is already completed. Use fresh=true to restart.",
                ]);
            }

            if ($existingLog->canResume()) {
                throw ValidationException::withMessages([
                    'bundle' => "Migration for {$proUid} was interrupted. Use resume=true to continue.",
                ]);
            }
        }

        $log = MigrationLog::forProUid($proUid);
        $log->start($bundle);

        return $this->runMigrationBundle($bundle, $log);
    }

    /**
     * @return array<string, mixed>
     */
    public function resumeMigrationBundle(array $bundle): array
    {
        return $this->applyMigrationBundle($bundle, resume: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMigrationLog(string $proUid): array
    {
        $log = MigrationLog::load($proUid);
        if ($log === null) {
            throw ValidationException::withMessages([
                'pro_uid' => "No migration log found for {$proUid}.",
            ]);
        }

        return $log->getVerificationReport();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMigrationLogs(): array
    {
        return MigrationLog::listAll();
    }

    /**
     * @return array<string, mixed>
     */
    private function runMigrationBundle(array $bundle, MigrationLog $log): array
    {
        $source = $bundle['source'] ?? [];
        $title = $this->resolveMigrationProcessName((string) ($source['title'] ?? 'Migrated Process'));
        $translator = new TriggerTranslator();
        $migrationWarnings = [];

        if (!$log->isStepDone(MigrationLog::STEP_CREATE_PROCESS)) {
            try {
                $processPayload = [
                    'name' => $title,
                    'description' => 'Migrated from PM3: ' . ($source['pro_uid'] ?? 'unknown'),
                ];

                if (!empty($bundle['bpmn']['xml']) && is_string($bundle['bpmn']['xml'])) {
                    $sanitized = MigrationBpmn::sanitizeXmlIds($bundle['bpmn']['xml']);
                    $bundle = MigrationBpmn::remapBundleElementIds($bundle, $sanitized['id_map']);
                    $processPayload['bpmn'] = $sanitized['xml'];
                    $migrationWarnings = array_merge($migrationWarnings, $sanitized['warnings']);
                }

                $process = $this->createProcess($processPayload);
                $log->setProcessId((int) $process->id);
                $log->completeStep(MigrationLog::STEP_CREATE_PROCESS);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_CREATE_PROCESS, $exception->getMessage());
                throw $exception;
            }
        }

        $process = Process::findOrFail($log->getProcessId());
        $scripts = $log->getArtifacts('scripts');
        $screens = $log->getArtifacts('screens');
        $collections = $log->getArtifacts('collections');
        $screenWarnings = $log->getStepResult(MigrationLog::STEP_CREATE_SCREENS)['warnings'] ?? [];

        if (!$log->isStepDone(MigrationLog::STEP_CREATE_SCRIPTS)) {
            try {
                $existingPm3Uids = collect($scripts)
                    ->pluck('pm3_uid')
                    ->filter()
                    ->all();

                foreach ($bundle['triggers'] ?? [] as $trigger) {
                    if (!is_array($trigger)) {
                        continue;
                    }

                    $pm3Uid = $trigger['TRI_UID'] ?? $trigger['tri_uid'] ?? null;
                    if (is_string($pm3Uid) && in_array($pm3Uid, $existingPm3Uids, true)) {
                        continue;
                    }

                    $code = $trigger['TRI_WEBBOT'] ?? $trigger['tri_webbot'] ?? '';
                    if ($code === '') {
                        continue;
                    }

                    $scriptTitle = $this->resolveUniqueAssetTitle(
                        (string) ($trigger['TRI_TITLE'] ?? $trigger['tri_title'] ?? 'Migrated Trigger'),
                        Script::class,
                    );
                    $scriptDescription = trim((string) ($trigger['TRI_DESCRIPTION'] ?? $trigger['tri_description'] ?? ''));
                    $script = $this->createScript([
                        'title' => $scriptTitle,
                        'description' => $scriptDescription !== '' ? $scriptDescription : $scriptTitle,
                        'code' => $translator->translate($code),
                        'language' => 'php',
                    ]);

                    $artifact = [
                        'pm3_uid' => $pm3Uid,
                        'pm4_id' => $script->id,
                        'title' => $script->title,
                    ];
                    $scripts[] = $artifact;
                    $log->addArtifact('scripts', $artifact);
                }

                $log->completeStep(MigrationLog::STEP_CREATE_SCRIPTS, ['count' => count($scripts)]);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_CREATE_SCRIPTS, $exception->getMessage());
                throw $exception;
            }
        }

        if (!$log->isStepDone(MigrationLog::STEP_CREATE_SCREENS)) {
            try {
                $existingPm3Uids = collect($screens)
                    ->pluck('pm3_uid')
                    ->filter()
                    ->all();
                $stylesheets = is_array($bundle['stylesheets'] ?? null) ? $bundle['stylesheets'] : [];

                foreach ($bundle['dynaforms'] ?? [] as $dynaform) {
                    if (!is_array($dynaform)) {
                        continue;
                    }

                    $pm3Uid = $dynaform['DYN_UID'] ?? $dynaform['dyn_uid'] ?? null;
                    if (is_string($pm3Uid) && in_array($pm3Uid, $existingPm3Uids, true)) {
                        continue;
                    }

                    $created = $this->createScreenFromDynaform($dynaform, $stylesheets);
                    $screen = $created['screen'];

                    $artifact = [
                        'pm3_uid' => $pm3Uid,
                        'pm4_id' => $screen->id,
                        'title' => $screen->title,
                    ];
                    $screens[] = $artifact;
                    $log->addArtifact('screens', $artifact);

                    foreach ($created['nested_screens'] as $nestedScreen) {
                        $nestedArtifact = [
                            'pm3_uid' => null,
                            'pm4_id' => $nestedScreen['pm4_id'],
                            'title' => $nestedScreen['title'],
                            'grid_variable' => $nestedScreen['grid_variable'] ?? null,
                        ];
                        $screens[] = $nestedArtifact;
                        $log->addArtifact('screens', $nestedArtifact);
                    }

                    foreach ($created['warnings'] as $warning) {
                        $screenWarnings[] = $screen->title . ': ' . $warning;
                    }
                }

                $log->completeStep(MigrationLog::STEP_CREATE_SCREENS, [
                    'count' => count($screens),
                    'warnings' => $screenWarnings,
                ]);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_CREATE_SCREENS, $exception->getMessage());
                throw $exception;
            }
        }

        $linkPayload = $this->buildLinkPayload($bundle, $screens, $scripts);

        $linkResult = $log->getStepResult(MigrationLog::STEP_LINK_ASSETS)
            ?? ['linked_screens' => [], 'linked_scripts' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_LINK_ASSETS)) {
            try {
                $linkResult = $this->autoLinkMigrationAssets((string) $process->id, $linkPayload);
                $log->completeStep(MigrationLog::STEP_LINK_ASSETS, $linkResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_LINK_ASSETS, $exception->getMessage());
                throw $exception;
            }
        }

        $identityMap = $this->normalizeIdentityMap($bundle['identity_map'] ?? null);
        $assignmentResult = $log->getStepResult(MigrationLog::STEP_APPLY_ASSIGNMENTS)
            ?? ['applied' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_APPLY_ASSIGNMENTS)) {
            try {
                if (($bundle['task_assignments'] ?? []) !== []) {
                    $assignmentResult = $this->applyTaskAssignments((string) $process->id, array_merge($linkPayload, [
                        'task_assignments' => $bundle['task_assignments'],
                        'identity_map' => $identityMap,
                    ]));
                }
                $log->completeStep(MigrationLog::STEP_APPLY_ASSIGNMENTS, $assignmentResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_APPLY_ASSIGNMENTS, $exception->getMessage());
                throw $exception;
            }
        }

        $variableResult = $log->getStepResult(MigrationLog::STEP_APPLY_VARIABLES)
            ?? ['applied' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_APPLY_VARIABLES)) {
            try {
                $process->refresh();
                $variableResult = (new ProcessVariableMigrator())->apply($process, $bundle['variables'] ?? []);
                $log->completeStep(MigrationLog::STEP_APPLY_VARIABLES, $variableResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_APPLY_VARIABLES, $exception->getMessage());
                throw $exception;
            }
        }

        $bpmnEnhancerResult = $log->getStepResult(MigrationLog::STEP_BPMN_ENHANCE)
            ?? ['applied_timers' => [], 'applied_sub_processes' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_BPMN_ENHANCE)) {
            try {
                $process->refresh();
                $bpmnEnhancerResult = (new BpmnEnhancer())->apply($process, array_merge($linkPayload, [
                    'timer_events' => $bundle['timer_events'] ?? [],
                    'case_schedulers' => $bundle['case_schedulers'] ?? [],
                    'sub_processes' => $bundle['sub_processes'] ?? [],
                    'subprocess_map' => $bundle['subprocess_map'] ?? [],
                    'subprocess_start_event_map' => $bundle['subprocess_start_event_map'] ?? [],
                ]));
                $log->completeStep(MigrationLog::STEP_BPMN_ENHANCE, $bpmnEnhancerResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_BPMN_ENHANCE, $exception->getMessage());
                throw $exception;
            }
        }

        $propertiesResult = $log->getStepResult(MigrationLog::STEP_STORE_METADATA)
            ?? ['applied_metadata' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_STORE_METADATA)) {
            try {
                $propertiesResult = $this->storeMigrationProperties((string) $process->id, $bundle);
                $log->completeStep(MigrationLog::STEP_STORE_METADATA, $propertiesResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_STORE_METADATA, $exception->getMessage());
                throw $exception;
            }
        }

        $webResult = $log->getStepResult(MigrationLog::STEP_WEB_ENTRY)
            ?? ['applied' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_WEB_ENTRY)) {
            try {
                $webResult = $this->applyWebEntry((string) $process->id, [
                    'web_entries' => $bundle['web_entries'] ?? [],
                    'screens' => $screens,
                ]);
                $log->completeStep(MigrationLog::STEP_WEB_ENTRY, $webResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_WEB_ENTRY, $exception->getMessage());
                throw $exception;
            }
        }

        $stepTriggersResult = $log->getStepResult(MigrationLog::STEP_STEP_TRIGGERS)
            ?? ['applied' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_STEP_TRIGGERS)) {
            try {
                $stepTriggersResult = $this->applyStepTriggers((string) $process->id, array_merge($linkPayload, [
                    'scripts' => $scripts,
                ]));
                $log->completeStep(MigrationLog::STEP_STEP_TRIGGERS, $stepTriggersResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_STEP_TRIGGERS, $exception->getMessage());
                throw $exception;
            }
        }

        $collectionWarnings = $log->getStepResult(MigrationLog::STEP_COLLECTIONS)['warnings'] ?? [];
        if (!$log->isStepDone(MigrationLog::STEP_COLLECTIONS)) {
            try {
                $existingPm3Uids = collect($collections)
                    ->pluck('pm3_uid')
                    ->filter()
                    ->all();

                foreach ($bundle['report_tables'] ?? [] as $reportTable) {
                    if (!is_array($reportTable)) {
                        continue;
                    }

                    $pm3Uid = $reportTable['rep_tab_uid'] ?? $reportTable['REP_TAB_UID'] ?? null;
                    if (is_string($pm3Uid) && in_array($pm3Uid, $existingPm3Uids, true)) {
                        continue;
                    }

                    try {
                        $created = $this->createCollectionFromReportTable(
                            $reportTable,
                            $reportTable['fields'] ?? []
                        );
                        $collection = $created['collection'];

                        $artifact = [
                            'pm3_uid' => $pm3Uid,
                            'pm4_id' => $collection->id,
                            'name' => $collection->name,
                        ];
                        $collections[] = $artifact;
                        $log->addArtifact('collections', $artifact);

                        foreach ($created['warnings'] as $warning) {
                            $collectionWarnings[] = $collection->name . ': ' . $warning;
                        }
                    } catch (ValidationException $exception) {
                        $collectionWarnings[] = ($reportTable['rep_tab_title'] ?? 'Report table')
                            . ': ' . implode(' ', $exception->validator->errors()->all());
                    }
                }

                $log->completeStep(MigrationLog::STEP_COLLECTIONS, [
                    'count' => count($collections),
                    'warnings' => $collectionWarnings,
                ]);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_COLLECTIONS, $exception->getMessage());
                throw $exception;
            }
        }

        $abeResult = $log->getStepResult(MigrationLog::STEP_ABE)
            ?? ['applied' => [], 'warnings' => []];
        if (!$log->isStepDone(MigrationLog::STEP_ABE)) {
            try {
                if (($bundle['abe_configurations'] ?? []) !== []) {
                    $abeResult = $this->applyAbeConfiguration((string) $process->id, array_merge($linkPayload, [
                        'abe_configurations' => $bundle['abe_configurations'],
                        'screens' => $screens,
                        'email_server_map' => $bundle['email_server_map'] ?? [],
                    ]));
                }
                $log->completeStep(MigrationLog::STEP_ABE, $abeResult);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_ABE, $exception->getMessage());
                throw $exception;
            }
        }

        $result = [
            'process_id' => $process->id,
            'process_name' => $process->name,
            'scripts' => $scripts,
            'screens' => $screens,
            'collections' => $collections,
            'linked_screens' => $linkResult['linked_screens'] ?? [],
            'linked_scripts' => $linkResult['linked_scripts'] ?? [],
            'applied_assignments' => $assignmentResult['applied'] ?? [],
            'applied_variables' => $variableResult['applied'] ?? [],
            'applied_timers' => $bpmnEnhancerResult['applied_timers'] ?? [],
            'applied_sub_processes' => $bpmnEnhancerResult['applied_sub_processes'] ?? [],
            'applied_web_entries' => $webResult['applied'] ?? [],
            'applied_step_triggers' => $stepTriggersResult['applied'] ?? [],
            'applied_documents' => $propertiesResult['applied_metadata'] ?? [],
            'applied_abe' => $abeResult['applied'] ?? [],
            'warnings' => array_values(array_filter(array_merge(
                $migrationWarnings,
                $screenWarnings,
                $linkResult['warnings'] ?? [],
                $assignmentResult['warnings'] ?? [],
                $variableResult['warnings'] ?? [],
                $bpmnEnhancerResult['warnings'] ?? [],
                $propertiesResult['warnings'] ?? [],
                $webResult['warnings'] ?? [],
                $stepTriggersResult['warnings'] ?? [],
                $collectionWarnings,
                $abeResult['warnings'] ?? [],
                is_array($bundle['bpmn']['warnings'] ?? null) ? $bundle['bpmn']['warnings'] : []
            ))),
        ];

        if (!$log->isStepDone(MigrationLog::STEP_FINALIZE)) {
            try {
                $result['validation'] = $this->validateProcess((string) $process->id);
                $result['gap_report'] = (new MigrationGapReport())->build($bundle, $result);
                $log->completeStep(MigrationLog::STEP_FINALIZE, [
                    'validation' => $result['validation'],
                    'gap_report' => $result['gap_report'],
                ]);
            } catch (\Throwable $exception) {
                $log->fail(MigrationLog::STEP_FINALIZE, $exception->getMessage());
                throw $exception;
            }
        } else {
            $finalizeResult = $log->getStepResult(MigrationLog::STEP_FINALIZE) ?? [];
            $result['validation'] = $finalizeResult['validation'] ?? $this->validateProcess((string) $process->id);
            $result['gap_report'] = $finalizeResult['gap_report']
                ?? (new MigrationGapReport())->build($bundle, $result);
        }

        $log->complete($result);
        $result['migration_log'] = $log->toSummary();

        return $result;
    }

    private function resolveBundleProUid(array $bundle): string
    {
        $source = is_array($bundle['source'] ?? null) ? $bundle['source'] : [];
        $proUid = $source['pro_uid'] ?? null;

        if (!is_string($proUid) || $proUid === '') {
            throw ValidationException::withMessages([
                'bundle.source.pro_uid' => 'Required for migration logging and resume.',
            ]);
        }

        return $proUid;
    }

    /**
     * @return array{
     *     applied_metadata: array<int, string>,
     *     warnings: array<int, string>
     * }
     */
    public function storeMigrationProperties(string $processId, array $bundle): array
    {
        $propertyMap = [
            'document_steps' => 'migration_document_steps',
            'document_definitions' => 'migration_document_definitions',
            'timer_events' => 'migration_timer_events',
            'case_schedulers' => 'migration_case_schedulers',
            'sub_processes' => 'migration_sub_processes',
            'web_entry_events' => 'migration_web_entry_events',
        ];

        $process = Process::findOrFail($processId);
        $properties = is_array($process->properties) ? $process->properties : [];
        $appliedMetadata = [];
        $warnings = [];

        foreach ($propertyMap as $bundleKey => $propertyKey) {
            $value = $bundle[$bundleKey] ?? [];
            if ($value === []) {
                continue;
            }

            $properties[$propertyKey] = $value;
            $appliedMetadata[] = $bundleKey;
        }

        if ($appliedMetadata === []) {
            return ['applied_metadata' => [], 'warnings' => []];
        }

        McpSupport::ensureActingUser($process);
        $this->platform()->updateProcess($process, ['properties' => $properties]);
        $process->refresh();

        return [
            'applied_metadata' => $appliedMetadata,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{applied: array<int, mixed>, warnings: array<int, string>}
     */
    public function applyWebEntry(string $processId, array $payload): array
    {
        if (($payload['web_entries'] ?? []) === []) {
            return ['applied' => [], 'warnings' => []];
        }

        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $startNode = $xpath->query('//bpmn:startEvent')->item(0);

        if (!($startNode instanceof \DOMElement)) {
            return ['applied' => [], 'warnings' => ['No start event found for web entry.']];
        }

        $screensByPm3 = $this->indexAssetsByPm3($payload['screens'] ?? []);
        $applied = [];
        $warnings = [];

        foreach ($payload['web_entries'] as $webEntry) {
            if (!is_array($webEntry)) {
                continue;
            }

            $dynUid = $webEntry['dyn_uid'] ?? null;
            $screenId = is_string($dynUid) ? ($screensByPm3[$dynUid] ?? null) : null;
            if ($screenId === null) {
                $warnings[] = 'Web entry screen was not migrated.';
                continue;
            }

            $config = [
                'web_entry' => [
                    'require_valid_session' => ($webEntry['we_authentication'] ?? '') !== 'guest',
                    'screen_id' => (int) $screenId,
                    'completed_action' => 'SCREEN',
                    'completed_screen_id' => (int) $screenId,
                    'mode' => 'NEW',
                ],
            ];

            $this->setPmAttributeOnNode($startNode, 'config', json_encode($config));
            $applied[] = [
                'we_uid' => $webEntry['we_uid'] ?? null,
                'screen_id' => $screenId,
            ];
        }

        $this->saveProcessBpmn($process, $definitions);

        if (method_exists($process, 'manageCustomRoutes')) {
            $process->manageCustomRoutes();
        }

        return ['applied' => $applied, 'warnings' => $warnings];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{applied: array<int, mixed>, warnings: array<int, string>}
     */
    public function applyStepTriggers(string $processId, array $payload): array
    {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $taskElementMap = MigrationBpmn::buildTaskElementMap($payload, $xpath);

        $scriptsByPm3 = $this->indexAssetsByPm3($payload['scripts'] ?? []);
        $applied = [];
        $warnings = [];
        $modified = false;
        $counter = 1;

        foreach ($payload['steps'] ?? [] as $step) {
            if (!is_array($step) || ($step['step_type'] ?? null) !== 'DYNAFORM') {
                continue;
            }

            $tasUid = $step['tas_uid'] ?? null;
            if (!is_string($tasUid) || $tasUid === '') {
                continue;
            }

            $elementId = $taskElementMap[$tasUid] ?? null;
            if ($elementId === null) {
                continue;
            }

            foreach ($step['triggers'] ?? [] as $trigger) {
                if (!is_array($trigger)) {
                    continue;
                }

                $triUid = $trigger['tri_uid'] ?? null;
                $type = strtoupper((string) ($trigger['type'] ?? ''));
                $scriptId = is_string($triUid) ? ($scriptsByPm3[$triUid] ?? null) : null;

                if ($scriptId === null) {
                    $warnings[] = "Step trigger {$triUid} was not migrated.";
                    continue;
                }

                $scriptNodeId = 'step_script_' . $counter++;
                if ($type === 'BEFORE') {
                    $this->insertStepScript($definitions, $xpath, $elementId, $scriptNodeId, $scriptId, 'before');
                } else {
                    $this->insertStepScript($definitions, $xpath, $elementId, $scriptNodeId, $scriptId, 'after');
                }

                $applied[] = [
                    'tas_uid' => $tasUid,
                    'tri_uid' => $triUid,
                    'type' => $type,
                    'script_node_id' => $scriptNodeId,
                ];
                $modified = true;
            }
        }

        if ($modified) {
            $this->saveProcessBpmn($process, $definitions);
        }

        return ['applied' => $applied, 'warnings' => $warnings];
    }

    private function buildLinkPayload(array $bundle, array $screens, array $scripts): array
    {
        return [
            'screens' => $screens,
            'scripts' => $scripts,
            'steps' => $bundle['steps'] ?? [],
            'tasks' => $bundle['tasks'] ?? [],
            'script_tasks' => $bundle['script_tasks'] ?? [],
            'task_element_map' => $bundle['task_element_map'] ?? [],
        ];
    }

    /**
     * @param  array<int, mixed>  $assets
     * @return array<string, string>
     */
    private function indexAssetsByPm3(array $assets): array
    {
        $map = [];

        foreach ($assets as $asset) {
            if (!is_array($asset)) {
                continue;
            }

            $pm3Uid = $asset['pm3_uid'] ?? null;
            $pm4Id = $asset['pm4_id'] ?? null;
            if (is_string($pm3Uid) && $pm3Uid !== '' && $pm4Id !== null) {
                $map[$pm3Uid] = (string) $pm4Id;
            }
        }

        return $map;
    }

    private function insertStepScript(
        DOMDocument $definitions,
        DOMXPath $xpath,
        string $taskElementId,
        string $scriptNodeId,
        string $scriptId,
        string $position
    ): void {
        $processNode = $xpath->query('//bpmn:process')->item(0);
        if (!($processNode instanceof \DOMElement)) {
            return;
        }

        $scriptNode = $definitions->createElementNS('http://www.omg.org/spec/BPMN/20100524/MODEL', 'bpmn:scriptTask');
        $scriptNode->setAttribute('id', $scriptNodeId);
        $scriptNode->setAttribute('name', 'Step Script');
        $this->setPmAttributeOnNode($scriptNode, 'scriptRef', $scriptId);
        $processNode->appendChild($scriptNode);

        if ($position === 'before') {
            foreach ($xpath->query("//bpmn:sequenceFlow[@targetRef='{$taskElementId}']") as $flowNode) {
                if ($flowNode instanceof \DOMElement) {
                    $flowNode->setAttribute('targetRef', $scriptNodeId);
                }
            }

            $bridgeFlow = $definitions->createElementNS('http://www.omg.org/spec/BPMN/20100524/MODEL', 'bpmn:sequenceFlow');
            $bridgeFlow->setAttribute('id', $scriptNodeId . '_to_task');
            $bridgeFlow->setAttribute('sourceRef', $scriptNodeId);
            $bridgeFlow->setAttribute('targetRef', $taskElementId);
            $processNode->appendChild($bridgeFlow);

            return;
        }

        $targets = [];
        foreach ($xpath->query("//bpmn:sequenceFlow[@sourceRef='{$taskElementId}']") as $flowNode) {
            if ($flowNode instanceof \DOMElement) {
                $target = $flowNode->getAttribute('targetRef');
                if ($target !== '') {
                    $targets[] = $target;
                }
                $flowNode->setAttribute('targetRef', $scriptNodeId);
            }
        }

        foreach ($targets as $index => $targetId) {
            $outFlow = $definitions->createElementNS('http://www.omg.org/spec/BPMN/20100524/MODEL', 'bpmn:sequenceFlow');
            $outFlow->setAttribute('id', $scriptNodeId . '_out_' . $index);
            $outFlow->setAttribute('sourceRef', $scriptNodeId);
            $outFlow->setAttribute('targetRef', $targetId);
            $processNode->appendChild($outFlow);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function applyAbeConfiguration(string $processId, array $payload): array
    {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $taskElementMap = MigrationBpmn::buildTaskElementMap($payload, $xpath);

        $screensByPm3 = $this->indexAssetsByPm3($payload['screens'] ?? []);

        $emailServerMap = is_array($payload['email_server_map'] ?? null)
            ? $payload['email_server_map']
            : [];

        $applied = [];
        $warnings = [];
        $modified = false;

        foreach ($payload['abe_configurations'] ?? [] as $abe) {
            if (!is_array($abe)) {
                continue;
            }

            $tasUid = $abe['tas_uid'] ?? $abe['TAS_UID'] ?? null;
            if (!is_string($tasUid) || $tasUid === '') {
                continue;
            }

            $elementId = $taskElementMap[$tasUid] ?? null;
            if ($elementId === null) {
                $warnings[] = "No BPMN element found for ABE task {$tasUid}.";
                continue;
            }

            $node = $xpath->query("//*[@id='{$elementId}']")->item(0);
            if (!($node instanceof \DOMElement)) {
                $warnings[] = "BPMN element {$elementId} not found for ABE task {$tasUid}.";
                continue;
            }

            $dynUid = $abe['dyn_uid'] ?? $abe['DYN_UID'] ?? null;
            $screenEmailRef = is_string($dynUid) ? ($screensByPm3[$dynUid] ?? '') : '';

            if ($screenEmailRef === '') {
                $warnings[] = "ABE task {$tasUid}: email screen dynaform was not migrated.";
            }

            $pm4Config = is_array($abe['pm4_config_email'] ?? null) ? $abe['pm4_config_email'] : [];
            $emailServerUid = $abe['abe_email_server_uid'] ?? $abe['ABE_EMAIL_SERVER_UID'] ?? null;
            $emailServer = is_string($emailServerUid)
                ? (string) ($emailServerMap[$emailServerUid] ?? '')
                : '';

            if ($emailServer === '' && is_string($emailServerUid) && $emailServerUid !== '') {
                $warnings[] = "ABE task {$tasUid}: PM3 email server {$emailServerUid} was not mapped to PM4.";
            }

            $configEmail = [
                'subject' => (string) ($pm4Config['subject']
                    ?? $abe['abe_subject_field']
                    ?? $abe['ABE_SUBJECT_FIELD']
                    ?? 'RE: {{ task.name }}'),
                'requireLogin' => (bool) ($pm4Config['requireLogin']
                    ?? $abe['abe_force_login']
                    ?? $abe['ABE_FORCE_LOGIN']
                    ?? false),
                'emailServer' => $emailServer,
                'screenEmailRef' => $screenEmailRef !== '' ? (int) $screenEmailRef : '',
                'email_notifications' => ['notifications' => []],
            ];

            $screenCompleteRef = $pm4Config['screenCompleteRef'] ?? '';
            if ($screenCompleteRef !== '' && $screenCompleteRef !== null) {
                $configEmail['screenCompleteRef'] = $screenCompleteRef;
            }

            $this->setPmAttributeOnNode($node, 'isActionsByEmail', 'true');
            $this->setPmAttributeOnNode($node, 'configEmail', json_encode($configEmail));

            if (($abe['abe_type'] ?? $abe['ABE_TYPE'] ?? null) === 'CUSTOM') {
                $warnings[] = "ABE task {$tasUid} uses CUSTOM type; verify PM4 email screen layout.";
            }

            $applied[] = [
                'tas_uid' => $tasUid,
                'element_id' => $elementId,
                'screen_email_ref' => $screenEmailRef,
            ];
            $modified = true;
        }

        if ($modified) {
            $this->saveProcessBpmn($process, $definitions);
        }

        return [
            'process_id' => $process->id,
            'applied' => $applied,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function applyTaskAssignments(string $processId, array $payload): array
    {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $taskElementMap = MigrationBpmn::buildTaskElementMap($payload, $xpath);

        $userMap = is_array($payload['identity_map']['users'] ?? null)
            ? $payload['identity_map']['users']
            : [];
        $groupMap = is_array($payload['identity_map']['groups'] ?? null)
            ? $payload['identity_map']['groups']
            : [];

        $applied = [];
        $warnings = [];
        $modified = false;

        foreach ($payload['task_assignments'] ?? [] as $assignment) {
            if (!is_array($assignment)) {
                continue;
            }

            $tasUid = $assignment['tas_uid'] ?? null;
            if (!is_string($tasUid) || $tasUid === '') {
                continue;
            }

            $elementId = $taskElementMap[$tasUid] ?? null;
            if ($elementId === null) {
                $warnings[] = "No BPMN element found for PM3 task {$tasUid}.";
                continue;
            }

            $node = $xpath->query("//*[@id='{$elementId}']")->item(0);
            if (!($node instanceof \DOMElement)) {
                $warnings[] = "BPMN element {$elementId} not found for task {$tasUid}.";
                continue;
            }

            $pm4Assignment = (string) ($assignment['pm4_assignment'] ?? 'user_group');
            $this->setPmAttributeOnNode($node, 'assignment', $pm4Assignment);

            if (in_array($pm4Assignment, ['user', 'group', 'user_group', 'self_service'], true)) {
                $mappedUsers = $this->mapIdentityIds($assignment['user_uids'] ?? [], $userMap);
                $mappedGroups = $this->mapIdentityIds($assignment['group_uids'] ?? [], $groupMap);

                if (($assignment['user_uids'] ?? []) !== [] && $mappedUsers === []) {
                    $warnings[] = "Task {$tasUid}: PM3 user assignments were not mapped to PM4 IDs.";
                }

                if (($assignment['group_uids'] ?? []) !== [] && $mappedGroups === []) {
                    $warnings[] = "Task {$tasUid}: PM3 group assignments were not mapped to PM4 IDs.";
                }

                $this->setPmAttributeOnNode($node, 'assignedUsers', implode(',', $mappedUsers));
                $this->setPmAttributeOnNode($node, 'assignedGroups', implode(',', $mappedGroups));
            }

            if (in_array($pm4Assignment, ['process_variable', 'rule_expression', 'user_by_id'], true)) {
                $usersExpression = $this->convertPm3Variable($assignment['assign_variable'] ?? null);
                $groupsExpression = $this->convertPm3Variable($assignment['group_variable'] ?? null);

                if ($usersExpression !== '') {
                    $this->setPmAttributeOnNode($node, 'assignedUsers', $usersExpression);
                }

                if ($groupsExpression !== '') {
                    $this->setPmAttributeOnNode($node, 'assignedGroups', $groupsExpression);
                }
            }

            $applied[] = [
                'tas_uid' => $tasUid,
                'element_id' => $elementId,
                'pm4_assignment' => $pm4Assignment,
            ];
            $modified = true;
        }

        if ($modified) {
            $this->saveProcessBpmn($process, $definitions);
        }

        return [
            'process_id' => $process->id,
            'applied' => $applied,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $reportTable
     * @param  array<int, mixed>  $fields
     * @return array{collection: Collection, warnings: array<int, string>}
     */
    public function createCollectionFromReportTable(array $reportTable, array $fields = []): array
    {
        if (!class_exists(Collection::class)) {
            throw ValidationException::withMessages([
                'collection' => ['package-collections is not installed.'],
            ]);
        }

        $title = $reportTable['rep_tab_title']
            ?? $reportTable['REP_TAB_TITLE']
            ?? $reportTable['rep_tab_name']
            ?? $reportTable['REP_TAB_NAME']
            ?? 'Migrated Collection';
        $warnings = [];

        if (!empty($reportTable['rep_tab_connection']) || !empty($reportTable['REP_TAB_CONNECTION'])) {
            $warnings[] = 'External DB connection was not migrated; collection stores metadata only.';
        }

        $screens = $this->createMigrationCollectionScreens($title);
        $userId = Auth::id() ?? User::firstOrFail()->id;
        $columns = $this->buildCollectionColumns($fields);

        $collection = new Collection();
        $collection->fill([
            'name' => $title,
            'description' => 'Migrated from PM3 report table '
                . ($reportTable['rep_tab_uid'] ?? $reportTable['REP_TAB_UID'] ?? 'unknown'),
            'create_screen_id' => $screens['create'],
            'read_screen_id' => $screens['read'],
            'update_screen_id' => $screens['update'],
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ]);
        $collection->columns = $columns;
        $collection->saveOrFail();

        return [
            'collection' => $collection,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{process_id: string, valid: bool, errors: array<int, mixed>}
     */
    public function validateProcess(string $processId): array
    {
        $process = Process::findOrFail($processId);
        $errors = MigrationBpmn::validate($process->bpmn);

        return [
            'process_id' => $process->id,
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>|string  $configJson
     * @return array<string, mixed>
     */
    private function parseConfigJson(array|string $configJson): array
    {
        if (is_array($configJson)) {
            return $configJson;
        }

        $decoded = json_decode($configJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'config_json' => ['Invalid JSON: ' . json_last_error_msg()],
            ]);
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function defaultScreenCategoryId(): int|string
    {
        return ScreenCategory::firstOrCreate(
            ['name' => McpSupport::SCREEN_CATEGORY],
            ['status' => 'ACTIVE']
        )->id;
    }

    private function defaultProcessCategoryId(): int|string
    {
        return ProcessCategory::firstOrCreate(
            ['name' => McpSupport::PROCESS_CATEGORY],
            ['status' => 'ACTIVE']
        )->id;
    }

    private function defaultScriptCategoryId(): int|string
    {
        return ScriptCategory::firstOrCreate(
            ['name' => McpSupport::SCRIPT_CATEGORY],
            ['status' => 'ACTIVE']
        )->id;
    }

    private function resolveMigrationProcessName(string $title): string
    {
        $name = trim($title);
        if ($name === '') {
            $name = 'Migrated Process';
        }

        $name = preg_replace('/[^a-zA-Z0-9 _\-]+/', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        if ($name === '') {
            $name = 'Migrated Process';
        }

        $base = $name;
        $suffix = 2;
        while (Process::where('name', $name)->exists()) {
            $name = $base . ' ' . $suffix;
            $suffix++;
        }

        return $name;
    }

    /**
     * @param  class-string<Script|Screen>  $modelClass
     */
    private function resolveUniqueAssetTitle(string $title, string $modelClass): string
    {
        $name = trim($title);
        if ($name === '') {
            $name = 'Migrated Asset';
        }

        $base = $name;
        $suffix = 2;
        while ($modelClass::where('title', $name)->exists()) {
            $name = $base . ' ' . $suffix;
            $suffix++;
        }

        return $name;
    }

    private function setBpmnElementAttribute(
        string $processId,
        string $elementId,
        string $attribute,
        string $value
    ): Process {
        $process = Process::findOrFail($processId);
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);

        $this->setPmAttribute($xpath, $elementId, $attribute, $value);
        $this->saveProcessBpmn($process, $definitions);

        return $process;
    }

    private function saveProcessBpmn(Process $process, DOMDocument $definitions): void
    {
        $this->platform()->persistProcessBpmn($process, $definitions->saveXML());
    }

    private function platform(): PlatformWriter
    {
        return $this->platformWriter ??= app(PlatformWriter::class);
    }

    private function setPmAttribute(DOMXPath $xpath, string $elementId, string $attribute, string $value): void
    {
        $node = $xpath->query("//*[@id='{$elementId}']")->item(0);

        if (!($node instanceof \DOMElement)) {
            throw ValidationException::withMessages([
                'element_id' => ["Element not found in BPMN: {$elementId}"],
            ]);
        }

        $this->setPmAttributeOnNode($node, $attribute, $value);
    }

    private function setPmAttributeOnNode(\DOMElement $node, string $attribute, string $value): void
    {
        $node->setAttributeNS(
            WorkflowServiceProvider::PROCESS_MAKER_NS,
            $attribute,
            $value
        );
    }

    /**
     * @return array{users: array<string, string>, groups: array<string, string>}
     */
    private function normalizeIdentityMap(mixed $map): array
    {
        if (!is_array($map)) {
            return ['users' => [], 'groups' => []];
        }

        return [
            'users' => is_array($map['users'] ?? null) ? $map['users'] : [],
            'groups' => is_array($map['groups'] ?? null) ? $map['groups'] : [],
        ];
    }

    private function findBpmnTaskNode(DOMXPath $xpath, string $elementId): ?\DOMElement
    {
        foreach (['//bpmn:task', '//bpmn:userTask'] as $expression) {
            $node = $xpath->query("{$expression}[@id='{$elementId}']")->item(0);
            if ($node instanceof \DOMElement) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $pm3Uids
     * @param  array<string, mixed>  $map
     * @return array<int, string>
     */
    private function mapIdentityIds(array $pm3Uids, array $map): array
    {
        $mapped = [];

        foreach ($pm3Uids as $pm3Uid) {
            if (!is_string($pm3Uid) || $pm3Uid === '') {
                continue;
            }

            $pm4Id = $map[$pm3Uid] ?? null;
            if ($pm4Id === null || $pm4Id === '') {
                continue;
            }

            $mapped[] = (string) $pm4Id;
        }

        return array_values(array_unique($mapped));
    }

    private function convertPm3Variable(mixed $variable): string
    {
        return (new TriggerTranslator())->pm3VariableToMustache($variable);
    }

    /**
     * @return array{create: int|string, read: int|string, update: int|string}
     */
    private function createMigrationCollectionScreens(string $title): array
    {
        $config = [['name' => $title, 'items' => []]];

        return [
            'create' => $this->createScreen([
                'title' => $title . ' Create',
                'config_json' => $config,
            ])->id,
            'read' => $this->createScreen([
                'title' => $title . ' Read',
                'config_json' => $config,
            ])->id,
            'update' => $this->createScreen([
                'title' => $title . ' Update',
                'config_json' => $config,
            ])->id,
        ];
    }

    /**
     * @param  array<int, mixed>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function buildCollectionColumns(array $fields): array
    {
        $columns = [
            [
                'label' => '#',
                'field' => 'id',
                'sortable' => true,
                'default' => true,
                'format' => 'int',
                'mask' => null,
            ],
            [
                'label' => 'Created',
                'field' => 'created_at',
                'sortable' => true,
                'default' => true,
                'format' => 'datetime',
                'mask' => null,
            ],
        ];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = $field['rep_var_field'] ?? $field['REP_VAR_FIELD'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $slug = $this->slugFieldName($name);
            $columns[] = [
                'label' => $field['rep_var_title'] ?? $field['REP_VAR_TITLE'] ?? $name,
                'field' => 'data.' . $slug,
                'sortable' => true,
                'default' => true,
                'format' => $this->mapReportVarFormat($field),
                'mask' => null,
            ];
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function mapReportVarFormat(array $field): string
    {
        $type = strtolower((string) ($field['rep_var_type'] ?? $field['REP_VAR_TYPE'] ?? 'string'));

        return match ($type) {
            'integer', 'int' => 'int',
            'float', 'double', 'decimal' => 'float',
            'date' => 'date',
            'datetime' => 'datetime',
            'boolean', 'bool' => 'boolean',
            default => 'string',
        };
    }

    private function slugFieldName(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_]+/', '_', $value) ?? 'field';
        $value = trim($value, '_');

        return $value !== '' ? strtolower($value) : 'field';
    }

    /**
     * @param  array<int, mixed>  $stylesheets
     * @param  array<int, array<string, mixed>>  $fieldRegistry
     */
    private function mergeCustomCss(?string $dynaformCss, array $stylesheets, array $fieldRegistry): ?string
    {
        $cssConverter = new CssScopeConverter();
        $chunks = [];

        if (is_string($dynaformCss) && trim($dynaformCss) !== '') {
            $chunks[] = trim($dynaformCss);
        }

        foreach ($stylesheets as $stylesheet) {
            if (!is_array($stylesheet)) {
                continue;
            }

            $content = $stylesheet['content'] ?? null;
            if (!is_string($content) || trim($content) === '') {
                continue;
            }

            $converted = $cssConverter->convert(trim($content), $fieldRegistry);
            if (is_string($converted['css']) && $converted['css'] !== '') {
                $chunks[] = $converted['css'];
            }
        }

        if ($chunks === []) {
            return null;
        }

        return implode("\n\n", array_unique($chunks));
    }
}
