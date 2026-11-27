<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Models\User;

class UserCatalog
{
    private const DEFAULT_PER_PAGE = 15;

    /**
     * @var list<string>
     */
    private const ORDER_COLUMNS = ['id', 'username', 'firstname', 'lastname', 'email', 'status'];

    /**
     * Same gate shape as Api\UserController::index for process authors.
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

        $query = User::nonSystem();
        $filter = trim((string) ($filters['filter'] ?? ''));
        if ($filter !== '') {
            $like = '%' . $filter . '%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('username', 'like', $like)
                    ->orWhere('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'ACTIVE');
        }

        $page = $query
            ->orderBy($filters['order_by'] ?? 'username', $filters['order_direction'] ?? 'asc')
            ->paginate(
                $filters['per_page'] ?? self::DEFAULT_PER_PAGE,
                ['id', 'username', 'firstname', 'lastname', 'email', 'status', 'is_administrator'],
                'page',
                $filters['page'] ?? 1,
            );

        return [
            'data' => collect($page->items())->map(static fn (User $user): array => [
                'id' => $user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'status' => $user->status,
                'is_administrator' => (bool) $user->is_administrator,
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
        if ($user->can('view-users')
            || $user->can('create-processes')
            || $user->can('edit-processes')) {
            return;
        }

        throw new AuthorizationException('Not authorized to view users.');
    }
}
