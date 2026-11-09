<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\User;

final class McpActor
{
    public static function resolve(?Authenticatable $user, string $resourceLabel): User
    {
        if ($user instanceof User) {
            return $user;
        }

        $admin = self::consoleAdministrator();
        if ($admin instanceof User) {
            Auth::setUser($admin);

            return $admin;
        }

        throw new AuthorizationException(
            'Authenticate with a ProcessMaker API token before using ' . $resourceLabel . ' tools.'
        );
    }

    public static function assertCan(User $user, string $permission): void
    {
        if ($user->can($permission)) {
            return;
        }

        throw new AuthorizationException('You do not have the ' . $permission . ' permission.');
    }

    private static function consoleAdministrator(): ?User
    {
        if (!app()->runningInConsole() || app()->runningUnitTests() || app()->environment('production')) {
            return null;
        }

        $admin = User::query()->where('is_administrator', true)->orderBy('id')->first();

        return $admin instanceof User ? $admin : null;
    }
}
