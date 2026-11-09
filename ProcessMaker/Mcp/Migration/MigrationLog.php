<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use Illuminate\Support\Facades\Storage;

class MigrationLog
{
    public const STEP_CREATE_PROCESS = 'create_process';

    public const STEP_CREATE_SCRIPTS = 'create_scripts';

    public const STEP_CREATE_SCREENS = 'create_screens';

    public const STEP_LINK_ASSETS = 'link_assets';

    public const STEP_APPLY_ASSIGNMENTS = 'apply_assignments';

    public const STEP_APPLY_VARIABLES = 'apply_variables';

    public const STEP_BPMN_ENHANCE = 'bpmn_enhance';

    public const STEP_STORE_METADATA = 'store_metadata';

    public const STEP_WEB_ENTRY = 'web_entry';

    public const STEP_STEP_TRIGGERS = 'step_triggers';

    public const STEP_COLLECTIONS = 'collections';

    public const STEP_ABE = 'abe';

    public const STEP_FINALIZE = 'finalize';

    public const STEPS = [
        self::STEP_CREATE_PROCESS,
        self::STEP_CREATE_SCRIPTS,
        self::STEP_CREATE_SCREENS,
        self::STEP_LINK_ASSETS,
        self::STEP_APPLY_ASSIGNMENTS,
        self::STEP_APPLY_VARIABLES,
        self::STEP_BPMN_ENHANCE,
        self::STEP_STORE_METADATA,
        self::STEP_WEB_ENTRY,
        self::STEP_STEP_TRIGGERS,
        self::STEP_COLLECTIONS,
        self::STEP_ABE,
        self::STEP_FINALIZE,
    ];

    private function __construct(
        private readonly string $proUid,
        private array $data,
    ) {
    }

    public static function forProUid(string $proUid): self
    {
        return new self($proUid, []);
    }

    public static function load(string $proUid): ?self
    {
        $path = self::storagePath($proUid);
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $decoded = json_decode(Storage::disk('local')->get($path), true);
        if (!is_array($decoded)) {
            return null;
        }

        return new self($proUid, $decoded);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listAll(): array
    {
        if (!Storage::disk('local')->exists('migration-logs')) {
            return [];
        }

        $summaries = [];
        foreach (Storage::disk('local')->files('migration-logs') as $path) {
            if (!str_ends_with($path, '.json')) {
                continue;
            }

            $decoded = json_decode(Storage::disk('local')->get($path), true);
            if (!is_array($decoded)) {
                continue;
            }

            $proUid = $decoded['pro_uid'] ?? basename($path, '.json');
            $log = new self((string) $proUid, $decoded);
            $summaries[] = $log->toSummary();
        }

        usort($summaries, static fn (array $a, array $b): int => strcmp(
            (string) ($b['updated_at'] ?? ''),
            (string) ($a['updated_at'] ?? '')
        ));

        return $summaries;
    }

    public static function storagePath(string $proUid): string
    {
        $safeUid = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $proUid) ?: 'unknown';

        return 'migration-logs/' . $safeUid . '.json';
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    public function start(array $bundle): void
    {
        $now = now()->toIso8601String();
        $source = is_array($bundle['source'] ?? null) ? $bundle['source'] : [];

        $this->data = [
            'migration_id' => $this->proUid,
            'pro_uid' => $this->proUid,
            'process_title' => $source['title'] ?? 'Migrated Process',
            'status' => 'in_progress',
            'started_at' => $now,
            'updated_at' => $now,
            'completed_at' => null,
            'failed_at' => null,
            'failed_step' => null,
            'error' => null,
            'process_id' => null,
            'steps' => array_fill_keys(self::STEPS, ['status' => 'pending']),
            'artifacts' => [
                'scripts' => [],
                'screens' => [],
                'collections' => [],
            ],
            'step_results' => [],
            'completed_steps' => [],
            'pending_steps' => self::STEPS,
            'result' => null,
        ];

        $this->save();
    }

    public function delete(): void
    {
        Storage::disk('local')->delete(self::storagePath($this->proUid));
        $this->data = [];
    }

    public function canResume(): bool
    {
        return in_array($this->data['status'] ?? '', ['in_progress', 'failed'], true)
            && !empty($this->data['process_id']);
    }

    public function getStatus(): string
    {
        return (string) ($this->data['status'] ?? 'unknown');
    }

    public function getProcessId(): ?int
    {
        $processId = $this->data['process_id'] ?? null;

        return is_numeric($processId) ? (int) $processId : null;
    }

    public function setProcessId(int $processId): void
    {
        $this->data['process_id'] = $processId;
        $this->touch();
        $this->save();
    }

    public function isStepDone(string $step): bool
    {
        return ($this->data['steps'][$step]['status'] ?? null) === 'done';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getArtifacts(string $type): array
    {
        $artifacts = $this->data['artifacts'][$type] ?? [];

        return is_array($artifacts) ? $artifacts : [];
    }

    /**
     * @param  array<string, mixed>  $artifact
     */
    public function addArtifact(string $type, array $artifact): void
    {
        if (!isset($this->data['artifacts'][$type]) || !is_array($this->data['artifacts'][$type])) {
            $this->data['artifacts'][$type] = [];
        }

        $this->data['artifacts'][$type][] = $artifact;
        $this->touch();
        $this->save();
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    public function completeStep(string $step, ?array $result = null): void
    {
        $this->data['steps'][$step] = [
            'status' => 'done',
            'completed_at' => now()->toIso8601String(),
        ];

        if ($result !== null) {
            $this->data['step_results'][$step] = $result;
        }

        $this->data['completed_steps'] = array_values(array_filter(
            self::STEPS,
            fn (string $name): bool => ($this->data['steps'][$name]['status'] ?? null) === 'done'
        ));
        $this->data['pending_steps'] = array_values(array_filter(
            self::STEPS,
            fn (string $name): bool => ($this->data['steps'][$name]['status'] ?? null) !== 'done'
        ));

        $this->touch();
        $this->save();
    }

    public function fail(string $step, string $message): void
    {
        $this->data['status'] = 'failed';
        $this->data['failed_at'] = now()->toIso8601String();
        $this->data['failed_step'] = $step;
        $this->data['error'] = $message;
        $this->data['steps'][$step] = array_merge(
            is_array($this->data['steps'][$step] ?? null) ? $this->data['steps'][$step] : [],
            [
                'status' => 'failed',
                'failed_at' => now()->toIso8601String(),
                'error' => $message,
            ]
        );
        $this->touch();
        $this->save();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function complete(array $result): void
    {
        $this->data['status'] = 'completed';
        $this->data['completed_at'] = now()->toIso8601String();
        $this->data['failed_at'] = null;
        $this->data['failed_step'] = null;
        $this->data['error'] = null;
        $this->data['result'] = $result;
        $this->data['completed_steps'] = self::STEPS;
        $this->data['pending_steps'] = [];
        $this->touch();
        $this->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStepResult(string $step): ?array
    {
        $result = $this->data['step_results'][$step] ?? null;

        return is_array($result) ? $result : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'migration_id' => $this->data['migration_id'] ?? $this->proUid,
            'pro_uid' => $this->proUid,
            'process_title' => $this->data['process_title'] ?? null,
            'status' => $this->data['status'] ?? 'unknown',
            'process_id' => $this->getProcessId(),
            'started_at' => $this->data['started_at'] ?? null,
            'updated_at' => $this->data['updated_at'] ?? null,
            'completed_at' => $this->data['completed_at'] ?? null,
            'failed_at' => $this->data['failed_at'] ?? null,
            'failed_step' => $this->data['failed_step'] ?? null,
            'error' => $this->data['error'] ?? null,
            'completed_steps' => $this->data['completed_steps'] ?? [],
            'pending_steps' => $this->data['pending_steps'] ?? self::STEPS,
            'artifacts_summary' => [
                'scripts' => count($this->getArtifacts('scripts')),
                'screens' => count($this->getArtifacts('screens')),
                'collections' => count($this->getArtifacts('collections')),
            ],
            'log_path' => self::storagePath($this->proUid),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getVerificationReport(): array
    {
        return [
            'summary' => $this->toSummary(),
            'artifacts' => $this->data['artifacts'] ?? [],
            'step_results' => $this->data['step_results'] ?? [],
            'steps' => $this->data['steps'] ?? [],
        ];
    }

    private function touch(): void
    {
        $this->data['updated_at'] = now()->toIso8601String();
    }

    private function save(): void
    {
        Storage::disk('local')->put(
            self::storagePath($this->proUid),
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
