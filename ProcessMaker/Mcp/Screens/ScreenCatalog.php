<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Screens;

use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Events\ScreenCreated;
use ProcessMaker\Events\ScreenDeleted;
use ProcessMaker\Events\ScreenUpdated;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Validation\CategoryRule;

class ScreenCatalog
{
    private const VIEW = 'view-screens';

    private const CREATE = 'create-screens';

    private const EDIT = 'edit-screens';

    private const DELETE = 'delete-screens';

    private const DEFAULT_PER_PAGE = 15;

    public function __construct(private readonly ScreenConfigValidator $validator)
    {
    }

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['title', 'type', 'updated_at', 'created_at', 'id'];

    /**
     * @var list<string>
     */
    private const JSON_FIELDS = ['config', 'computed', 'watchers', 'preview_presets'];

    private const JSON_FIELD_MESSAGE = ' must be a JSON object or array.';

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

        throw new AuthorizationException('Authenticate with a ProcessMaker API token before using screen tools.');
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
            'type' => 'nullable|string|max:20',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'order_by' => 'nullable|in:' . implode(',', self::ORDER_COLUMNS),
            'order_direction' => 'nullable|in:asc,desc',
            'include_system' => 'nullable|boolean',
        ])->validate();

        $page = $this->filteredQuery($filters)
            ->orderBy($filters['order_by'] ?? 'title', $filters['order_direction'] ?? 'asc')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'title', 'description', 'type', 'screen_category_id', 'updated_at'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(fn (Screen $screen): array => $this->summary($screen))->all(),
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

        return $this->detail(Screen::query()->findOrFail($id));
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
        $this->assertConfigValid($validated['config'] ?? null, $validated['type'] ?? null);

        $screen = new Screen();
        $screen->fill($validated);
        $screen->saveOrFail();
        $this->forgetProjectAssetCache($user);

        $payload = $screen->getAttributes();
        $payload['tmp_screen_category_id'] = $validated['screen_category_id'] ?? null;
        ScreenCreated::dispatch($payload);

        return $this->detail($screen->refresh());
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

        $screen = Screen::query()->findOrFail((int) $validated['id']);
        $attributes = $this->writable($validated);
        if ($attributes === []) {
            throw ValidationException::withMessages([
                'screen' => 'Provide at least one field to update: title, description, type, screen_category_id, config, computed, watchers, preview_presets, or custom_css.',
            ]);
        }

        if (array_key_exists('config', $attributes)) {
            $this->assertConfigValid(
                $attributes['config'],
                $attributes['type'] ?? $screen->type,
            );
        }

        $original = $screen->getOriginal();
        $screen->fill($attributes);
        $screen->saveOrFail();
        ScreenUpdated::dispatch($screen, $screen->getChanges(), $original);
        ScreenCacheFactory::getScreenCache()->clearCompiledAssets();

        return $this->detail($screen->refresh());
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, errors: list<array{path: ?string, code: string, message: string}>, warnings: list<array{path: ?string, code: string, message: string}>}
     */
    public function validate(User $user, array $input): array
    {
        $this->assertCan($user, self::VIEW);
        $validated = validator($input, [
            'config' => 'nullable|array',
            'type' => 'nullable|string|max:20',
            'id' => 'nullable|integer|min:1',
        ])->validate();

        $config = $validated['config'] ?? null;
        $type = $validated['type'] ?? null;
        if (($config === null || $config === []) && isset($validated['id'])) {
            $screen = Screen::query()->findOrFail((int) $validated['id']);
            $config = is_array($screen->config) ? $screen->config : [];
            $type = $type ?? $screen->type;
        }

        return $this->validator->validate(is_array($config) ? $config : null, is_string($type) ? $type : null);
    }

    /**
     * @return array{id: int, deleted: true}
     */
    public function delete(User $user, int $id): array
    {
        $this->assertCan($user, self::DELETE);
        $screen = Screen::query()->findOrFail($id);
        if ((int) $screen->is_default === 1) {
            throw ValidationException::withMessages([
                'is_default' => 'Default screens cannot be deleted.',
            ]);
        }

        $screen->delete();
        ScreenDeleted::dispatch($screen);
        $this->forgetProjectAssetCache($user);

        return [
            'id' => $id,
            'deleted' => true,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Screen::query();
        if (empty($filters['include_system'])) {
            $query->nonSystem();
        }

        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function createRules(array $input): array
    {
        return array_merge(Screen::rules(), $this->optionalFieldRules($input));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function updateRules(array $input): array
    {
        $rules = [];
        if (array_key_exists('title', $input)) {
            $rules['title'] = ['required', Rule::unique('screens')->ignore($input['id'] ?? null), 'alpha_spaces'];
        }
        if (array_key_exists('description', $input)) {
            $rules['description'] = 'required|string';
        }
        if (array_key_exists('type', $input)) {
            $rules['type'] = 'required|string|max:20';
        }
        if (array_key_exists('screen_category_id', $input)) {
            $rules['screen_category_id'] = [new CategoryRule(Screen::query()->find($input['id'] ?? null))];
        }

        return array_merge($rules, $this->optionalFieldRules($input));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function optionalFieldRules(array $input): array
    {
        $rules = [];
        foreach (self::JSON_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $rules[$field] = 'array';
            }
        }
        if (array_key_exists('custom_css', $input)) {
            $rules['custom_css'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        if (isset($input['screen_category_id']) && is_int($input['screen_category_id'])) {
            $input['screen_category_id'] = (string) $input['screen_category_id'];
        }

        foreach (self::JSON_FIELDS as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            if (is_string($input[$field])) {
                $input[$field] = $this->decodeJson($field, $input[$field]);
            }
            if ($input[$field] === null) {
                $input[$field] = [];
            }
        }

        if (isset($input['config']) && is_array($input['config'])) {
            $input['config'] = $this->normalizeConfig($input['config']);
        }

        if (isset($input['preview_presets']) && is_array($input['preview_presets'])) {
            $input['preview_presets'] = $this->normalizePreviewPresets($input['preview_presets']);
        }

        return $input;
    }

    /**
     * Designer stores each preset.data as a JSON string so empty objects survive PHP json_decode.
     *
     * @param array<int, mixed> $presets
     * @return list<array{title: string, data: string}>
     */
    private function normalizePreviewPresets(array $presets): array
    {
        $normalized = [];
        foreach ($presets as $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $title = isset($preset['title']) && is_string($preset['title']) ? $preset['title'] : '';
            $data = $preset['data'] ?? '{}';
            if (is_array($data)) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
            } elseif (!is_string($data)) {
                $data = '{}';
            } else {
                try {
                    json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    $data = '{}';
                }
            }
            $normalized[] = [
                'title' => $title,
                'data' => $data,
            ];
        }

        return $normalized;
    }

    /**
     * Ensure pages/items are Screen Builder–safe (missing inspector crashes the designer).
     *
     * @param array<int, mixed> $pages
     * @return array<int, mixed>
     */
    private function normalizeConfig(array $pages): array
    {
        foreach ($pages as $pageIndex => $page) {
            if (!is_array($page)) {
                continue;
            }
            $items = $page['items'] ?? [];
            $pages[$pageIndex]['items'] = is_array($items)
                ? array_map(fn (mixed $item): mixed => $this->normalizeConfigItem($item), $items)
                : [];
        }

        return $pages;
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    private function normalizeConfigItem(mixed $item): mixed
    {
        if (!is_array($item)) {
            return $item;
        }

        if (!isset($item['inspector']) || !is_array($item['inspector'])) {
            $item['inspector'] = [];
        }

        if (empty($item['container']) || !isset($item['items']) || !is_array($item['items'])) {
            return $item;
        }

        foreach ($item['items'] as $columnIndex => $columnItems) {
            if (!is_array($columnItems)) {
                continue;
            }
            $item['items'][$columnIndex] = array_map(
                fn (mixed $nested): mixed => $this->normalizeConfigItem($nested),
                $columnItems,
            );
        }

        return $item;
    }

    /**
     * @return array<mixed>
     */
    private function decodeJson(string $field, string $value): array
    {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                $field => $field . self::JSON_FIELD_MESSAGE,
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => $field . self::JSON_FIELD_MESSAGE,
            ]);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function writable(array $input): array
    {
        $fields = ['title', 'description', 'type', 'screen_category_id', 'config', 'computed', 'custom_css', 'watchers', 'preview_presets'];

        return array_intersect_key($input, array_flip($fields));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Screen $screen): array
    {
        return [
            'id' => $screen->id,
            'title' => $screen->title,
            'description' => $screen->description,
            'type' => $screen->type,
            'screen_category_id' => $screen->screen_category_id,
            'updated_at' => $this->timestamp($screen->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Screen $screen): array
    {
        return [
            ...$this->summary($screen),
            'config' => $screen->config,
            'computed' => $screen->computed,
            'watchers' => $screen->watchers,
            'preview_presets' => $screen->preview_presets,
            'custom_css' => $screen->custom_css,
            'created_at' => $this->timestamp($screen->created_at),
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

    /**
     * @param array<int, mixed>|null $config
     */
    private function assertConfigValid(?array $config, ?string $type): void
    {
        $result = $this->validator->validate($config, $type);
        if ($result['ok']) {
            return;
        }

        $messages = array_map(
            static fn (array $issue): string => $issue['message'],
            $result['errors'],
        );

        throw ValidationException::withMessages([
            'config' => $messages,
        ]);
    }
}
