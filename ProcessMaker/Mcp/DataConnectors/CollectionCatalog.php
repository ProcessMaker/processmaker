<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\DataConnectors;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Mcp\Support\McpActor;
use ProcessMaker\Models\User;
use ProcessMaker\PackageHelper;
use ProcessMaker\Plugins\Collections\Models\Collection;
use ProcessMaker\Plugins\Collections\Models\Record;

class CollectionCatalog
{
    private const VIEW = 'view-collections';

    private const CREATE = 'create-collections';

    private const EDIT = 'edit-collections';

    private const DEFAULT_PER_PAGE = 15;

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['name', 'updated_at', 'created_at', 'id'];

    public function actor(?Authenticatable $user): User
    {
        return McpActor::resolve($user, 'collection');
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
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'order_by' => 'nullable|in:' . implode(',', self::ORDER_COLUMNS),
            'order_direction' => 'nullable|in:asc,desc',
        ])->validate();

        $page = $this->filteredQuery($filters)
            ->orderBy($filters['order_by'] ?? 'name', $filters['order_direction'] ?? 'asc')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'name', 'description', 'create_screen_id', 'read_screen_id', 'update_screen_id', 'type', 'updated_at'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(fn (Collection $row): array => $this->summary($row))->all(),
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

        return $this->detail(Collection::query()->findOrFail($id));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::CREATE);
        $validated = validator($input, Collection::rules())->validate();

        $collection = new Collection();
        $collection->fill($validated);
        if (isset($input['type'])) {
            $collection->type = $input['type'];
        }
        $collection->created_by_id = $user->id;
        $collection->updated_by_id = $user->id;
        $collection->saveOrFail();

        return $this->detail($collection->refresh());
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::EDIT);
        $validated = validator($input, array_merge([
            'id' => 'required|integer|min:1',
        ], $this->updateRules($input)))->validate();

        $collection = Collection::query()->findOrFail((int) $validated['id']);
        $attributes = array_intersect_key($validated, array_flip([
            'name',
            'description',
            'create_screen_id',
            'read_screen_id',
            'update_screen_id',
            'custom_title',
            'obfuscate',
            'signal_create',
            'signal_update',
            'signal_delete',
            'include_records_on_export',
            'type',
        ]));
        if ($attributes === []) {
            throw ValidationException::withMessages([
                'collection' => 'Provide at least one field to update.',
            ]);
        }

        $collection->fill($attributes);
        $collection->updated_by_id = $user->id;
        $collection->saveOrFail();

        return $this->detail($collection->refresh());
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function listRecords(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);
        $validated = validator($input, [
            'collection_id' => 'required|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ])->validate();

        $collection = Collection::query()->findOrFail((int) $validated['collection_id']);
        $this->assertCanOnCollection($user, $collection, 'view');

        $page = Record::fromCollection($collection)
            ->orderBy('id')
            ->paginate(
                $validated['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['*'],
                'page',
                $validated['page'] ?? 1,
            );

        return [
            'collection_id' => $collection->id,
            'data' => collect($page->items())->map(fn (Record $record): array => $this->recordSummary($record))->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createRecords(User $user, array $input): array
    {
        $this->assertPackage();
        McpActor::assertCan($user, self::VIEW);
        $validated = validator($input, [
            'collection_id' => 'required|integer|min:1',
            'records' => 'nullable|array|max:100',
            'records.*' => 'array',
            'data' => 'nullable|array',
        ])->validate();

        $rows = $validated['records'] ?? null;
        if ($rows === null && isset($validated['data'])) {
            $rows = [$validated['data']];
        }
        if ($rows === null || $rows === []) {
            throw ValidationException::withMessages([
                'records' => 'Provide records (array of objects) or data (single object).',
            ]);
        }

        $collection = Collection::query()->findOrFail((int) $validated['collection_id']);
        $this->assertCanOnCollection($user, $collection, 'create');

        $created = [];
        foreach ($rows as $row) {
            $record = $collection->createRecord(['data' => $row]);
            $created[] = $this->recordSummary($record);
        }

        return [
            'collection_id' => $collection->id,
            'created_count' => count($created),
            'data' => $created,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Collection::query()->nonSystem();
        $filter = $filters['filter'] ?? '';
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function updateRules(array $input): array
    {
        $rules = [];
        if (array_key_exists('name', $input)) {
            $rules['name'] = ['required', Rule::unique('collections')->ignore($input['id'] ?? null), 'alpha_spaces'];
        }
        if (array_key_exists('description', $input)) {
            $rules['description'] = 'required|string';
        }
        foreach (['create_screen_id', 'read_screen_id', 'update_screen_id'] as $field) {
            if (array_key_exists($field, $input)) {
                $rules[$field] = 'required|integer|min:1';
            }
        }
        if (array_key_exists('custom_title', $input)) {
            $rules['custom_title'] = 'nullable|string';
        }
        if (array_key_exists('obfuscate', $input)) {
            $rules['obfuscate'] = 'nullable|boolean';
        }
        foreach (['signal_create', 'signal_update', 'signal_delete', 'include_records_on_export'] as $field) {
            if (array_key_exists($field, $input)) {
                $rules[$field] = 'nullable|boolean';
            }
        }
        if (array_key_exists('type', $input)) {
            $rules['type'] = 'nullable|in:NORMAL,RAG';
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Collection $collection): array
    {
        $count = 0;
        $table = 'collection_' . $collection->id;
        if (DB::getSchemaBuilder()->hasTable($table)) {
            $count = (int) DB::table($table)->count();
        }

        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'type' => $collection->type,
            'create_screen_id' => $collection->create_screen_id,
            'read_screen_id' => $collection->read_screen_id,
            'update_screen_id' => $collection->update_screen_id,
            'record_count' => $count,
            'updated_at' => $this->timestamp($collection->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Collection $collection): array
    {
        return [
            ...$this->summary($collection),
            'custom_title' => $collection->custom_title,
            'obfuscate' => (bool) $collection->obfuscate,
            'columns' => $collection->columns,
            'signal_create' => (bool) $collection->signal_create,
            'signal_update' => (bool) $collection->signal_update,
            'signal_delete' => (bool) $collection->signal_delete,
            'created_at' => $this->timestamp($collection->created_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recordSummary(Record $record): array
    {
        return [
            'id' => $record->id,
            'uuid' => $record->uuid ?? null,
            'data' => $record->data,
            'created_at' => $this->timestamp($record->created_at),
            'updated_at' => $this->timestamp($record->updated_at),
        ];
    }

    private function assertCanOnCollection(User $user, Collection $collection, string $ability): void
    {
        // Policy abilities: view, create, update, delete
        if ($user->can($ability, $collection)) {
            return;
        }

        throw ValidationException::withMessages([
            'collection_id' => 'You do not have ' . $ability . ' permission on this collection.',
        ]);
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
        if (PackageHelper::isPackageInstalled(PackageHelper::PM_PACKAGE_COLLECTIONS)) {
            return;
        }

        throw ValidationException::withMessages([
            'package' => 'package-collections is not installed on this ProcessMaker instance.',
        ]);
    }
}
