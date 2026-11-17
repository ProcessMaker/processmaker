<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Events\ProcessCreated;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Validation\CategoryRule;

class ProcessCatalog
{
    private const VIEW = 'view-processes';

    private const CREATE = 'create-processes';

    private const EDIT = 'edit-processes';

    private const DEFAULT_PER_PAGE = 15;

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['name', 'status', 'updated_at', 'created_at', 'id'];

    public function __construct(private readonly ProcessBpmnValidator $validator)
    {
    }

    public function actor(?Authenticatable $user): User
    {
        if ($user instanceof User) {
            return $user;
        }

        $admin = $this->consoleAdministrator();
        if ($admin instanceof User) {
            Auth::setUser($admin);

            return $admin;
        }

        throw new AuthorizationException('Authenticate with a ProcessMaker API token before using process tools.');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function list(User $user, array $input): array
    {
        $this->assertCan($user, self::VIEW);
        $filters = validator($input, [
            'filter' => 'nullable|string|max:255',
            'status' => 'nullable|in:ACTIVE,INACTIVE,ARCHIVED',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'order_by' => 'nullable|in:' . implode(',', self::ORDER_COLUMNS),
            'order_direction' => 'nullable|in:asc,desc',
            'include_templates' => 'nullable|boolean',
        ])->validate();

        $page = $this->filteredQuery($filters)
            ->orderBy($filters['order_by'] ?? 'name', $filters['order_direction'] ?? 'asc')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'name', 'description', 'status', 'process_category_id', 'updated_at'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(fn (Process $process): array => $this->summary($process))->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(User $user, int $id, string $alternative = 'A'): array
    {
        $this->assertCan($user, self::VIEW);
        $alternative = $this->normalizeAlternative($alternative);

        return $this->detail(Process::query()->findOrFail($id), $alternative);
    }

    /**
     * Enable Alternative B for a process (clone draft A → B).
     *
     * @return array<string, mixed>
     */
    public function enableAlternativeB(User $user, int $id): array
    {
        $this->assertCan($user, self::EDIT);
        $process = Process::query()->findOrFail($id);
        $this->ensureAlternativeBExists($process);

        return $this->alternativesPayload($process);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(User $user, array $input): array
    {
        $this->assertCan($user, self::CREATE);
        $input = $this->normalize($input);
        $validated = validator($input, $this->createRules($input))->validate();

        if (array_key_exists('bpmn', $validated) && is_string($validated['bpmn']) && $validated['bpmn'] !== '') {
            $this->assertBpmnAcceptable($validated['bpmn']);
            $validated['bpmn'] = BpmnDocument::replaceHtmlEntities($validated['bpmn']);
        }

        $process = new Process();
        $process->fill($validated);
        $process->user_id = $user->id;
        if (empty($process->status)) {
            $process->status = 'ACTIVE';
        }
        if (empty($process->bpmn)) {
            $process->bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');
        }
        $process->saveOrFail();
        $this->forgetProjectAssetCache($user);

        $type = array_key_exists('bpmn', $input) && is_string($input['bpmn']) && $input['bpmn'] !== ''
            ? ProcessCreated::BPMN_CREATION
            : ProcessCreated::BLANK_CREATION;
        ProcessCreated::dispatch($process->refresh(), $type);

        return $this->detail($process->refresh());
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(User $user, array $input): array
    {
        $this->assertCan($user, self::EDIT);
        $input = $this->normalize($input);
        $validated = validator($input, array_merge([
            'id' => 'required|integer|min:1',
            'alternative' => 'nullable|in:A,B',
            'create_alternative_b' => 'nullable|boolean',
        ], $this->updateRules($input)))->validate();

        $process = Process::query()->findOrFail((int) $validated['id']);
        $alternative = $this->normalizeAlternative($validated['alternative'] ?? 'A');

        if (!empty($validated['create_alternative_b'])) {
            $this->ensureAlternativeBExists($process);
        }

        if ($alternative === 'B') {
            $this->assertAlternativeBReady($process);
        }

        $attributes = $this->writable($validated);
        if ($attributes === []) {
            throw ValidationException::withMessages([
                'process' => 'Provide at least one field to update: name, description, status, process_category_id, or bpmn.',
            ]);
        }

        if (isset($attributes['bpmn'])) {
            $this->assertBpmnAcceptable($attributes['bpmn']);
            $attributes['bpmn'] = BpmnDocument::replaceHtmlEntities($attributes['bpmn']);
        }

        $this->hydrateProcessFromAlternativeVersion($process, $alternative);

        if (isset($attributes['bpmn'])) {
            $process->fill($attributes);
            try {
                $process->alternative = $alternative;
                $process->saveDraft($alternative);
            } catch (TaskDoesNotHaveUsersException $e) {
                throw ValidationException::withMessages(['bpmn' => $e->getMessage()]);
            }
        } else {
            $process->fill($attributes);
            $process->saveOrFail();
        }

        return $this->detail($process->refresh(), $alternative);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, errors: list<array{nodeId: ?string, code: string, message: string}>, warnings: list<array{nodeId: ?string, code: string, message: string}>}
     */
    public function validate(User $user, array $input): array
    {
        $this->assertCan($user, self::VIEW);
        $validated = validator($input, [
            'bpmn' => 'nullable|string',
            'id' => 'nullable|integer|min:1',
            'alternative' => 'nullable|in:A,B',
        ])->validate();

        $bpmn = $validated['bpmn'] ?? null;
        if (($bpmn === null || $bpmn === '') && isset($validated['id'])) {
            $process = Process::query()->findOrFail((int) $validated['id']);
            $alternative = $this->normalizeAlternative($validated['alternative'] ?? 'A');
            $bpmn = $this->bpmnForAlternative($process, $alternative);
        }

        return $this->validator->validate(is_string($bpmn) ? $bpmn : null);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Process::query()->notArchived();
        if (empty($filters['include_templates'])) {
            $query->where(function (Builder $inner): void {
                $inner->where('is_template', false)->orWhereNull('is_template');
            });
        }

        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function createRules(array $input): array
    {
        $rules = Process::rules();
        if (array_key_exists('process_category_id', $input)) {
            $rules['process_category_id'] = [new CategoryRule(null)];
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function updateRules(array $input): array
    {
        $rules = [];
        if (array_key_exists('name', $input)) {
            $rules['name'] = ['required', Rule::unique('processes')->ignore($input['id'] ?? null), 'alpha_spaces'];
        }
        if (array_key_exists('description', $input)) {
            $rules['description'] = 'required|string';
        }
        if (array_key_exists('status', $input)) {
            $rules['status'] = 'in:ACTIVE,INACTIVE,ARCHIVED';
        }
        if (array_key_exists('process_category_id', $input)) {
            $rules['process_category_id'] = [new CategoryRule(Process::query()->find($input['id'] ?? null))];
        }
        if (array_key_exists('bpmn', $input)) {
            $rules['bpmn'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        if (isset($input['process_category_id']) && is_int($input['process_category_id'])) {
            $input['process_category_id'] = (string) $input['process_category_id'];
        }

        return $input;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function writable(array $input): array
    {
        return array_intersect_key($input, array_flip([
            'name',
            'description',
            'status',
            'process_category_id',
            'bpmn',
        ]));
    }

    private function assertBpmnAcceptable(string $bpmn): void
    {
        $result = $this->validator->validate($bpmn);
        if ($result['ok']) {
            return;
        }

        $messages = collect($result['errors'])->pluck('message')->unique()->implode(' ');

        throw ValidationException::withMessages([
            'bpmn' => $messages !== '' ? $messages : 'The bpm definition is not valid.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Process $process): array
    {
        return [
            'id' => $process->id,
            'name' => $process->name,
            'description' => $process->description,
            'status' => $process->status,
            'process_category_id' => $process->process_category_id,
            'updated_at' => $this->timestamp($process->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Process $process, string $alternative = 'A'): array
    {
        $alternative = $this->normalizeAlternative($alternative);
        $bpmn = $this->bpmnForAlternative($process, $alternative) ?? $process->bpmn;

        return [
            ...$this->summary($process),
            'alternative' => $alternative,
            'alternatives' => $this->alternativesMeta($process),
            'bpmn' => $bpmn,
            'created_at' => $this->timestamp($process->created_at),
        ];
    }

    private function normalizeAlternative(string $alternative): string
    {
        return strtoupper($alternative) === 'B' ? 'B' : 'A';
    }

    private function hydrateProcessFromAlternativeVersion(Process $process, string $alternative): void
    {
        $version = $this->versionForAlternative($process, $alternative);
        if ($version instanceof ProcessVersion) {
            $process->bpmn = $version->bpmn;
            $process->stages = $version->stages;
        }
        $process->alternative = $alternative;
    }

    private function versionForAlternative(Process $process, string $alternative): ?ProcessVersion
    {
        $version = $process->getDraftVersion($alternative);
        if ($version instanceof ProcessVersion) {
            return $version;
        }

        $version = $process->getDraftOrPublishedLatestVersion($alternative);

        return $version instanceof ProcessVersion ? $version : null;
    }

    private function bpmnForAlternative(Process $process, string $alternative): ?string
    {
        return $this->versionForAlternative($process, $alternative)?->bpmn;
    }

    /**
     * @return array{b_enabled: bool, package_installed: bool}
     */
    private function alternativesMeta(Process $process): array
    {
        if (!class_exists('ProcessMaker\Package\PackageABTesting\Models\Alternative')) {
            return [
                'package_installed' => false,
                'b_enabled' => false,
            ];
        }

        $record = \ProcessMaker\Package\PackageABTesting\Models\Alternative::forProcess($process)->first();

        return [
            'package_installed' => true,
            'b_enabled' => (bool) ($record?->version_b_enabled),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function alternativesPayload(Process $process): array
    {
        return [
            'id' => $process->id,
            'name' => $process->name,
            ...$this->alternativesMeta($process),
        ];
    }

    private function ensureAlternativeBExists(Process $process): void
    {
        if (!class_exists('ProcessMaker\Package\PackageABTesting\Models\Alternative')) {
            throw ValidationException::withMessages([
                'create_alternative_b' => 'package-ab-testing is not installed on this instance.',
            ]);
        }

        $alternative = \ProcessMaker\Package\PackageABTesting\Models\Alternative::findOrCreateForProcess($process);
        if ($alternative->version_b_enabled) {
            return;
        }

        $alternative->enableAlternativeB();
        $alternative->save();
    }

    private function assertAlternativeBReady(Process $process): void
    {
        $meta = $this->alternativesMeta($process);
        if (!$meta['package_installed']) {
            throw ValidationException::withMessages([
                'alternative' => 'Alternative B requires package-ab-testing.',
            ]);
        }
        if (!$meta['b_enabled']) {
            throw ValidationException::withMessages([
                'alternative' => 'Alternative B is not enabled for this process. Call enable-process-alternative-b or set create_alternative_b on update-process.',
            ]);
        }
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        return $value === null ? null : (string) $value;
    }

    private function assertCan(User $user, string $permission): void
    {
        if ($user->can($permission)) {
            return;
        }

        throw new AuthorizationException('You do not have the ' . $permission . ' permission.');
    }

    private function consoleAdministrator(): ?User
    {
        if (!app()->runningInConsole() || app()->runningUnitTests() || app()->environment('production')) {
            return null;
        }

        $admin = User::query()->where('is_administrator', true)->orderBy('id')->first();

        return $admin instanceof User ? $admin : null;
    }

    private function forgetProjectAssetCache(User $user): void
    {
        if (!hasPackage('package-projects')) {
            return;
        }

        Cache::forget('user_' . $user->id . '_project_assets');
    }
}
