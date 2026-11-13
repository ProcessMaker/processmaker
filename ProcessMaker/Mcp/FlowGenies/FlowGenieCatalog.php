<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\FlowGenies;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Mcp\Support\McpActor;
use ProcessMaker\Models\User;
use ProcessMaker\Package\PackageAi\Models\FlowGenie;
use ProcessMaker\Package\PackageAi\Models\FlowGenieCategory;

class FlowGenieCatalog
{
    private const VIEW = 'view-flow_genies';

    private const CREATE = 'create-flow_genies';

    private const EDIT = 'edit-flow_genies';

    private const DELETE = 'delete-flow_genies';

    private const DEFAULT_PER_PAGE = 15;

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['name', 'updated_at', 'created_at', 'id'];

    public function __construct(private readonly FlowGenieConfigValidator $validator)
    {
    }

    public function actor(?Authenticatable $user): User
    {
        return McpActor::resolve($user, 'FlowGenie');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function list(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);
        $filters = validator($input, [
            'filter' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'order_by' => 'nullable|in:' . implode(',', self::ORDER_COLUMNS),
            'order_direction' => 'nullable|in:asc,desc',
        ])->validate();

        $page = $this->filteredQuery($filters)
            ->orderBy($filters['order_by'] ?? 'name', $filters['order_direction'] ?? 'asc')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'uuid', 'name', 'description', 'flow_genie_category_id', 'updated_at'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(fn (FlowGenie $row): array => $this->summary($row))->all(),
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
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);

        return $this->detail(FlowGenie::query()->findOrFail($id));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function create(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::CREATE);
        $input = $this->normalizeInput($input);
        $validated = validator($input, $this->createRules($input))->validate();
        $config = $this->validator->normalize($validated['config'] ?? null);

        $flowGenie = new FlowGenie();
        $flowGenie->fill([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'flow_genie_category_id' => $validated['flow_genie_category_id'],
            'config' => $config,
        ]);
        $flowGenie->saveOrFail();

        return $this->detail($flowGenie->refresh());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function update(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::EDIT);
        $input = $this->normalizeInput($input);
        $validated = validator($input, array_merge([
            'id' => 'required|integer|min:1',
        ], $this->updateRules($input)))->validate();

        $flowGenie = FlowGenie::query()->findOrFail((int) $validated['id']);
        $attributes = [];
        foreach (['name', 'description', 'flow_genie_category_id'] as $field) {
            if (array_key_exists($field, $validated)) {
                $attributes[$field] = $validated[$field];
            }
        }
        if (array_key_exists('config', $validated)) {
            $attributes['config'] = $this->validator->normalize($validated['config']);
        }

        if ($attributes === []) {
            throw ValidationException::withMessages([
                'flow_genie' => 'Provide at least one field to update: name, description, flow_genie_category_id, or config.',
            ]);
        }

        $flowGenie->fill($attributes);
        $flowGenie->saveOrFail();

        return $this->detail($flowGenie->refresh());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ok: bool, deleted: bool, id: int}
     */
    public function delete(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::DELETE);
        $validated = validator($input, [
            'id' => 'required|integer|min:1',
        ])->validate();

        $flowGenie = FlowGenie::query()->findOrFail((int) $validated['id']);
        $id = (int) $flowGenie->id;
        $flowGenie->delete();

        return [
            'ok' => true,
            'deleted' => true,
            'id' => $id,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function listCategories(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);
        $filters = validator($input, [
            'filter' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ])->validate();

        $query = FlowGenieCategory::query()->nonSystem()->where('status', 'ACTIVE');

        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $query->where('name', 'like', '%' . $filter . '%');
        }

        $page = $query->orderBy('name')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'name', 'status'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(static fn (FlowGenieCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'status' => $category->status,
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ok: bool, errors: list<array<string, mixed>>, warnings: list<array<string, mixed>>, normalized?: array<string, mixed>}
     */
    public function validateConfig(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);
        $validated = validator($input, [
            'config' => 'nullable|array',
        ])->validate();

        $result = $this->validator->validate($validated['config'] ?? null);
        if ($result['ok'] && array_key_exists('config', $validated)) {
            $result['normalized'] = $this->validator->normalize($validated['config']);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = FlowGenie::query();
        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $query->filter($filter);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function createRules(array $input): array
    {
        return [
            'name' => ['required', 'string', 'alpha_spaces', Rule::unique('flow_genies', 'name')],
            'description' => 'required|string|max:300',
            'flow_genie_category_id' => 'required',
            'config' => 'nullable|array',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function updateRules(array $input): array
    {
        $id = $input['id'] ?? null;

        return [
            'name' => ['sometimes', 'string', 'alpha_spaces', Rule::unique('flow_genies', 'name')->ignore($id)],
            'description' => 'sometimes|string|max:300',
            'flow_genie_category_id' => 'sometimes',
            'config' => 'sometimes|array',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        if (isset($input['flow_genie_category_id']) && is_int($input['flow_genie_category_id'])) {
            $input['flow_genie_category_id'] = (string) $input['flow_genie_category_id'];
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(FlowGenie $flowGenie): array
    {
        $config = is_array($flowGenie->config) ? $flowGenie->config : [];

        return [
            'id' => $flowGenie->id,
            'name' => $flowGenie->name,
            'description' => $flowGenie->description,
            'flow_genie_category_id' => $flowGenie->flow_genie_category_id,
            'model' => $config['model'] ?? null,
            'provider' => $config['provider'] ?? null,
            'output_type' => $config['output_type'] ?? ($config['response_format'] ?? null),
            'has_instructions' => trim((string) ($config['instructions'] ?? $config['prompt'] ?? '')) !== '',
            'updated_at' => $this->timestamp($flowGenie->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(FlowGenie $flowGenie): array
    {
        return [
            ...$this->summary($flowGenie),
            'uuid' => $flowGenie->uuid,
            'config' => $this->redactConfig(is_array($flowGenie->config) ? $flowGenie->config : []),
            'created_at' => $this->timestamp($flowGenie->created_at),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function redactConfig(array $config): array
    {
        if (!empty($config['provider_api_key'])) {
            $config['provider_api_key'] = '[redacted]';
        }

        if (!empty($config['mcp_servers']) && is_array($config['mcp_servers'])) {
            $config['mcp_servers'] = array_map(function ($server) {
                if (!is_array($server)) {
                    return $server;
                }
                $auth = $server['config']['http_config']['authentication'] ?? null;
                if (!is_array($auth)) {
                    return $server;
                }
                foreach (['access_token', 'client_secret', 'cached_access_token', 'cached_refresh_token'] as $key) {
                    if (!empty($auth[$key])) {
                        $auth[$key] = '[redacted]';
                    }
                    if (!empty($auth['oauth_pkce'][$key])) {
                        $auth['oauth_pkce'][$key] = '[redacted]';
                    }
                    if (!empty($auth['oauth_client_credentials'][$key])) {
                        $auth['oauth_client_credentials'][$key] = '[redacted]';
                    }
                }
                $server['config']['http_config']['authentication'] = $auth;

                return $server;
            }, $config['mcp_servers']);
        }

        return $config;
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        return $value === null ? null : (string) $value;
    }

    private function assertPackage(): void
    {
        if (class_exists(FlowGenie::class)) {
            return;
        }

        throw ValidationException::withMessages([
            'package' => 'package-ai is not installed on this ProcessMaker instance.',
        ]);
    }
}
