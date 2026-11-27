<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Events\GroupCreated;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class GroupCatalog
{
    private const DEFAULT_PER_PAGE = 15;

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['id', 'name', 'status', 'updated_at', 'created_at'];

    /**
     * Same gate shape as Api\GroupController::index for process authors.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function list(User $actor, array $input): array
    {
        $this->assertCanList($actor);

        $filters = validator($input, [
            'filter' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'order_by' => 'nullable|in:' . implode(',', self::ORDER_COLUMNS),
            'order_direction' => 'nullable|in:asc,desc',
        ])->validate();

        $query = Group::query()->withCount('groupMembers');
        $filter = trim((string) ($filters['filter'] ?? ''));
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        // Exact (case-insensitive) name match, for resolving one group when names repeat.
        $exactName = trim((string) ($filters['name'] ?? ''));
        if ($exactName !== '') {
            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($exactName)]);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'ACTIVE');
        }

        $page = $query
            ->orderBy($filters['order_by'] ?? 'name', $filters['order_direction'] ?? 'asc')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'name', 'description', 'status', 'manager_id', 'updated_at'],
                'page',
                $filters['page'] ?? 1,
            );

        $items = collect($page->items());
        $sameName = $this->sameNameCounts($items->pluck('name')->all());

        return [
            'data' => $items->map(fn (Group $group): array => $this->summary($group, $sameName))->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    /**
     * Create a group. Same rules as Api\GroupController::store (unique name, ACTIVE or INACTIVE).
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(User $actor, array $input): array
    {
        if (!$actor->can('create-groups')) {
            throw new AuthorizationException('You do not have the create-groups permission.');
        }

        $data = validator($input, array_merge(Group::rules(), [
            'description' => 'nullable|string|max:255',
            'manager_id' => 'nullable|integer|exists:users,id',
        ]))->validate();

        $group = new Group();
        $group->fill($data + ['status' => 'ACTIVE']);
        // Same as Api\GroupController::store: never taken from tool arguments.
        if (config('password-policies.2fa_enabled', false)) {
            $group->enabled_2fa = true;
        }
        $group->saveOrFail();
        GroupCreated::dispatch($group);

        return $this->summary($group->loadCount('groupMembers'), []);
    }

    /**
     * Groups with the same name are ambiguous in assignments. Count them for the names on the page.
     *
     * @param list<string> $names
     * @return array<string, int>
     */
    private function sameNameCounts(array $names): array
    {
        if ($names === []) {
            return [];
        }

        return Group::query()
            ->whereIn('name', array_values(array_unique($names)))
            ->selectRaw('name, COUNT(*) as aggregate')
            ->groupBy('name')
            ->pluck('aggregate', 'name')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @param array<string, int> $sameName
     * @return array<string, mixed>
     */
    private function summary(Group $group, array $sameName): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'status' => $group->status,
            'manager_id' => $group->manager_id,
            'members_count' => (int) $group->group_members_count,
            'same_name_count' => $sameName[$group->name] ?? 1,
            'updated_at' => optional($group->updated_at)?->format('c'),
        ];
    }

    private function assertCanList(User $user): void
    {
        if ($user->can('view-groups')
            || $user->can('create-processes')
            || $user->can('edit-processes')) {
            return;
        }

        throw new AuthorizationException('Not authorized to view groups.');
    }
}
