<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\DataConnectors;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Mcp\Support\McpActor;
use ProcessMaker\Models\User;
use ProcessMaker\PackageHelper;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSource;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory;

class DataConnectorCatalog
{
    private const VIEW = 'view-data-sources';

    private const CREATE = 'create-data-sources';

    private const EDIT = 'edit-data-sources';

    private const DEFAULT_PER_PAGE = 15;

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['name', 'type', 'status', 'updated_at', 'created_at', 'id'];

    public function actor(?Authenticatable $user): User
    {
        return McpActor::resolve($user, 'data connector');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function list(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);
        $filters = validator($input, [
            'filter' => 'nullable|string|max:255',
            'type' => 'nullable|in:REST,SOAP',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
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
                ['id', 'name', 'description', 'type', 'authtype', 'status', 'data_source_category_id', 'api_base_url', 'endpoints', 'updated_at'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(fn (DataSource $row): array => $this->summary($row))->all(),
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

        return $this->detail(DataSource::query()->findOrFail($id));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::CREATE);
        $input = $this->normalize($input);
        $validated = validator($input, $this->createRules($input))->validate();
        $this->assertEndpoints($validated['endpoints'] ?? null);

        $dataSource = new DataSource();
        $dataSource->fill($this->writable($validated));
        if (!isset($validated['status'])) {
            $dataSource->status = 'ACTIVE';
        }
        if (!isset($validated['endpoints'])) {
            $dataSource->endpoints = [];
        }
        $dataSource->saveOrFail();

        return $this->detail($dataSource->refresh());
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::EDIT);
        $input = $this->normalize($input);
        $validated = validator($input, array_merge([
            'id' => 'required|integer|min:1',
        ], $this->updateRules($input)))->validate();

        $dataSource = DataSource::query()->findOrFail((int) $validated['id']);
        $attributes = $this->writable($validated);
        if ($attributes === []) {
            throw ValidationException::withMessages([
                'data_connector' => 'Provide at least one field to update: name, description, type, authtype, status, data_source_category_id, api_base_url, endpoints, credentials, or debug_mode.',
            ]);
        }

        if (array_key_exists('endpoints', $attributes)) {
            $this->assertEndpoints($attributes['endpoints']);
        }

        $dataSource->fill($attributes);
        $dataSource->saveOrFail();

        return $this->detail($dataSource->refresh());
    }

    /**
     * @param array<string, mixed> $input
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

        $query = DataSourceCategory::query()->where('is_system', false)->where('status', 'ACTIVE');
        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where('name', 'like', $like);
        }

        $page = $query->orderBy('name')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'name', 'status'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(static fn (DataSourceCategory $category): array => [
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
     * @param array<string, mixed> $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = DataSource::query()
            ->where('is_template', false)
            ->whereNull('asset_type');

        if (empty($filters['include_templates'])) {
            // already excluded templates above
        }

        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        } else {
            $query->where('type', 'REST');
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
        return [
            'name' => ['required', Rule::unique('data_sources'), 'alpha_spaces'],
            'description' => 'nullable|string',
            'type' => 'required|in:REST,SOAP',
            'authtype' => $this->authtypeRule($input['type'] ?? 'REST'),
            'status' => 'nullable|in:ACTIVE,INACTIVE',
            'data_source_category_id' => 'required',
            'api_base_url' => 'nullable|string|max:2048',
            'endpoints' => 'nullable|array',
            'credentials' => 'nullable|array',
            'debug_mode' => 'nullable|boolean',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function updateRules(array $input): array
    {
        $rules = [];
        if (array_key_exists('name', $input)) {
            $rules['name'] = ['required', Rule::unique('data_sources')->ignore($input['id'] ?? null), 'alpha_spaces'];
        }
        if (array_key_exists('description', $input)) {
            $rules['description'] = 'nullable|string';
        }
        if (array_key_exists('type', $input)) {
            $rules['type'] = 'required|in:REST,SOAP';
        }
        if (array_key_exists('authtype', $input) || array_key_exists('type', $input)) {
            $type = $input['type'] ?? DataSource::query()->find($input['id'] ?? null)?->type ?? 'REST';
            $rules['authtype'] = $this->authtypeRule((string) $type);
        }
        if (array_key_exists('status', $input)) {
            $rules['status'] = 'required|in:ACTIVE,INACTIVE';
        }
        if (array_key_exists('data_source_category_id', $input)) {
            $rules['data_source_category_id'] = 'required';
        }
        if (array_key_exists('api_base_url', $input)) {
            $rules['api_base_url'] = 'nullable|string|max:2048';
        }
        if (array_key_exists('endpoints', $input)) {
            $rules['endpoints'] = 'nullable|array';
        }
        if (array_key_exists('credentials', $input)) {
            $rules['credentials'] = 'nullable|array';
        }
        if (array_key_exists('debug_mode', $input)) {
            $rules['debug_mode'] = 'nullable|boolean';
        }

        return $rules;
    }

    private function authtypeRule(string $type): string
    {
        return match ($type) {
            'REST' => 'required|in:NONE,BASIC,OAUTH2_BEARER,OAUTH2_PASSWORD,OAUTH2_AUTHORIZATION_CODE',
            'SOAP' => 'required|in:PASSWORD,LOCAL_CERTIFICATE,WSDL_FILE',
            default => 'required',
        };
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        if (isset($input['data_source_category_id']) && is_int($input['data_source_category_id'])) {
            $input['data_source_category_id'] = (string) $input['data_source_category_id'];
        }

        if (isset($input['endpoints']) && is_string($input['endpoints'])) {
            $decoded = json_decode($input['endpoints'], true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'endpoints' => 'endpoints must be a JSON object map keyed by purpose.',
                ]);
            }
            $input['endpoints'] = $decoded;
        }

        if (isset($input['credentials']) && is_string($input['credentials'])) {
            $decoded = json_decode($input['credentials'], true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'credentials' => 'credentials must be a JSON object.',
                ]);
            }
            $input['credentials'] = $decoded;
        }

        if (isset($input['endpoints']) && is_array($input['endpoints'])) {
            $input['endpoints'] = $this->normalizeEndpoints($input['endpoints']);
        }

        return $input;
    }

    /**
     * @param array<string|int, mixed> $endpoints
     * @return array<string, mixed>
     */
    private function normalizeEndpoints(array $endpoints): array
    {
        $normalized = [];
        foreach ($endpoints as $key => $endpoint) {
            if (!is_array($endpoint)) {
                continue;
            }
            $purpose = (string) ($endpoint['purpose'] ?? $key);
            if ($purpose === '') {
                continue;
            }
            if ((string) $key !== $purpose) {
                throw ValidationException::withMessages([
                    'endpoints' => 'Endpoint map key must equal purpose for "' . $purpose . '".',
                ]);
            }
            $endpoint['purpose'] = $purpose;
            $normalized[$purpose] = $endpoint;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed>|null $endpoints
     */
    private function assertEndpoints(?array $endpoints): void
    {
        if ($endpoints === null) {
            return;
        }

        foreach ($endpoints as $key => $endpoint) {
            if (!is_array($endpoint)) {
                throw ValidationException::withMessages([
                    'endpoints' => 'Each endpoint must be an object.',
                ]);
            }
            $purpose = $endpoint['purpose'] ?? null;
            if ($purpose === null || $purpose === '') {
                throw ValidationException::withMessages([
                    'endpoints' => 'Each endpoint requires a purpose (resource name).',
                ]);
            }
            if (empty($endpoint['url'])) {
                throw ValidationException::withMessages([
                    'endpoints' => 'Endpoint "' . $purpose . '" requires a url.',
                ]);
            }
            if (empty($endpoint['method'])) {
                throw ValidationException::withMessages([
                    'endpoints' => 'Endpoint "' . $purpose . '" requires a method.',
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function writable(array $input): array
    {
        $fields = [
            'name',
            'description',
            'type',
            'authtype',
            'status',
            'data_source_category_id',
            'api_base_url',
            'endpoints',
            'credentials',
            'debug_mode',
        ];

        return array_intersect_key($input, array_flip($fields));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(DataSource $dataSource): array
    {
        return [
            'id' => $dataSource->id,
            'name' => $dataSource->name,
            'description' => $dataSource->description,
            'type' => $dataSource->type,
            'authtype' => $dataSource->authtype,
            'status' => $dataSource->status,
            'data_source_category_id' => $dataSource->data_source_category_id,
            'api_base_url' => $dataSource->api_base_url,
            'endpoint_purposes' => array_keys(is_array($dataSource->endpoints) ? $dataSource->endpoints : []),
            'updated_at' => $this->timestamp($dataSource->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(DataSource $dataSource): array
    {
        return [
            ...$this->summary($dataSource),
            'endpoints' => $dataSource->endpoints,
            'debug_mode' => (bool) $dataSource->debug_mode,
            'created_at' => $this->timestamp($dataSource->created_at),
            // credentials omitted on purpose — set via create/update only
        ];
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
        if (PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_DATA_SOURCES)) {
            return;
        }

        throw ValidationException::withMessages([
            'package' => 'package-data-sources is not installed on this ProcessMaker instance.',
        ]);
    }
}
