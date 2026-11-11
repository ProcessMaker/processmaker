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
use ProcessMaker\Models\Process;
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

    public function __construct(
        private readonly ProcessBpmnValidator $validator,
        private readonly BpmnLayoutService $layout,
    ) {
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
    public function find(User $user, int $id): array
    {
        $this->assertCan($user, self::VIEW);

        return $this->detail(Process::query()->findOrFail($id));
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
            $validated['bpmn'] = $this->layoutBpmnIfRequested($input, $validated['bpmn']);
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
        ], $this->updateRules($input)))->validate();

        $process = Process::query()->findOrFail((int) $validated['id']);
        $attributes = $this->writable($validated);
        if ($attributes === []) {
            throw ValidationException::withMessages([
                'process' => 'Provide at least one field to update: name, description, status, process_category_id, or bpmn.',
            ]);
        }

        if (isset($attributes['bpmn'])) {
            $attributes['bpmn'] = $this->layoutBpmnIfRequested($input, $attributes['bpmn']);
            $this->assertBpmnAcceptable($attributes['bpmn']);
            $attributes['bpmn'] = BpmnDocument::replaceHtmlEntities($attributes['bpmn']);
        }

        $process->fill($attributes);
        $process->saveOrFail();

        return $this->detail($process->refresh());
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
        ])->validate();

        $bpmn = $validated['bpmn'] ?? null;
        if (($bpmn === null || $bpmn === '') && isset($validated['id'])) {
            $bpmn = Process::query()->findOrFail((int) $validated['id'])->bpmn;
        }

        return $this->validator->validate(is_string($bpmn) ? $bpmn : null);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{bpmn: string, warnings: list<string>, layout_engine: string}
     */
    public function layoutBpmn(User $user, array $input): array
    {
        $this->assertCan($user, self::VIEW);
        $validated = validator($input, [
            'bpmn' => 'nullable|string',
            'id' => 'nullable|integer|min:1',
        ])->validate();

        $bpmn = $validated['bpmn'] ?? null;
        if (($bpmn === null || $bpmn === '') && isset($validated['id'])) {
            $bpmn = Process::query()->findOrFail((int) $validated['id'])->bpmn;
        }

        if (!is_string($bpmn) || trim($bpmn) === '') {
            throw ValidationException::withMessages([
                'bpmn' => 'BPMN XML is required (or pass process id).',
            ]);
        }

        $result = $this->layout->layout($bpmn);

        return [
            'bpmn' => $result['bpmn'],
            'warnings' => $result['warnings'],
            'layout_engine' => 'bpmn-auto-layout-extended',
        ];
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

    /**
     * @param array<string, mixed> $input
     */
    private function layoutBpmnIfRequested(array $input, string $bpmn): string
    {
        $needsLayout = $this->shouldAutoLayout($input)
            || $this->validator->lacksSequenceFlowDiagramInterchange($bpmn);

        if (!$needsLayout) {
            return $bpmn;
        }

        if (!config('mcp.bpmn_layout.enabled')) {
            return $bpmn;
        }

        return $this->layout->layout($bpmn)['bpmn'];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function shouldAutoLayout(array $input): bool
    {
        if (!config('mcp.bpmn_layout.enabled')) {
            return false;
        }

        $flag = $input['auto_layout_bpmn'] ?? true;
        if ($flag === false || $flag === 0 || $flag === '0' || $flag === 'false') {
            return false;
        }

        return true;
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
    private function detail(Process $process): array
    {
        return [
            ...$this->summary($process),
            'bpmn' => $process->bpmn,
            'created_at' => $this->timestamp($process->created_at),
        ];
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
