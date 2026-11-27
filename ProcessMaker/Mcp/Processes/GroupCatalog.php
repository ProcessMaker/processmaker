<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use Illuminate\Auth\Access\AuthorizationException;
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
            'status' => 'nullable|in:ACTIVE,INACTIVE',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'order_by' => 'nullable|in:' . implode(',', self::ORDER_COLUMNS),
            'order_direction' => 'nullable|in:asc,desc',
        ])->validate();

        $query = Group::query();
        $filter = trim((string) ($filters['filter'] ?? ''));
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
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

        return [
            'data' => collect($page->items())->map(static fn (Group $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'status' => $group->status,
                'manager_id' => $group->manager_id,
                'updated_at' => optional($group->updated_at)?->format('c'),
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
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
