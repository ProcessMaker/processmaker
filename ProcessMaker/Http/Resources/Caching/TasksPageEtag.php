<?php

namespace ProcessMaker\Http\Resources\Caching;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ProcessMaker\Filters\SaveSession;
use ProcessMaker\Http\Controllers\Api\UserConfigurationController;
use ProcessMaker\Managers\PackageManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Models\UserConfiguration;

class TasksPageEtag
{
    private const FEATURE_CONFIG_KEYS = [
        'app.api_timeout',
        'app.dateformat',
        'app.env',
        'app.open_ai_nlq_to_pmql',
        'app.screen.cache_enabled',
        'app.screen.cache_timeout',
        'app.screen.show_secure_handler_toggle',
        'app.task_drafts_enabled',
        'app.tce_customization_enable',
        'app.timezone',
        'app.url',
        'broadcasting.default',
        'broadcasting.connections.redis.host',
        'notifications.messages',
        'session.expire_warning',
        'session.lifetime',
        'translations.enabled',
    ];

    /**
     * Generate a quoted ETag from the stable context that affects the Tasks page shell.
     */
    public function getEtag(Request $request): string
    {
        return '"' . hash($this->hashAlgorithm(), json_encode($this->payload($request))) . '"';
    }

    /**
     * Build the complete content-affecting context used to validate the page.
     *
     * Volatile values such as CSRF tokens, session ids, and randomized asset URLs are
     * intentionally excluded because they would prevent useful conditional requests.
     */
    private function payload(Request $request): array
    {
        $user = $request->user();

        return [
            'route' => [
                'name' => $request->route()?->getName(),
                'path' => $request->path(),
                'router' => $request->route('router'),
                'query' => $this->sorted($request->query()),
            ],
            'user' => $this->userPayload($user),
            'tenant' => $this->tenantPayload(),
            'permissions_v' => $this->permissionsVersion($user),
            'session_content' => [
                'alert' => session('_alert'),
                'rememberme' => session('rememberme'),
            ],
            'saved_search_v' => $this->savedSearchPayload($user),
            'locale' => app()->getLocale(),
            'task_context' => [
                'user_filter' => $user ? SaveSession::getConfigFilter('taskFilter', $user) : null,
                'user_configuration' => $this->userConfigurationPayload($user),
                'task_drafts_enabled' => TaskDraft::draftsEnabled(),
            ],
            'features_v' => $this->featuresPayload(),
            'packages_v' => $this->packagesPayload(),
        ];
    }

    /**
     * Capture user fields emitted into the layout or used by Tasks page decisions.
     */
    private function userPayload(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'updated_at' => $this->dateValue($user->updated_at),
            'is_administrator' => $user->is_administrator,
            'status' => $user->status,
            'fullname' => $user->fullname,
            'avatar' => $user->avatar,
            'datetime_format' => $user->datetime_format,
            'timezone' => $user->timezone,
            'language' => $user->language,
        ];
    }

    /**
     * Include tenant identity because tenant config and assets can change the page shell.
     */
    private function tenantPayload(): ?array
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (!$tenant) {
            return null;
        }

        return [
            'id' => $tenant->id ?? null,
            'updated_at' => $this->dateValue($tenant->updated_at ?? null),
        ];
    }

    /**
     * Version the effective permission context used by Blade and frontend props.
     *
     * The full permission list can be expensive to rebuild, so this uses the session
     * snapshot plus lightweight assignment/version markers that are enough to
     * invalidate when direct user or direct group permission assignments change.
     */
    private function permissionsVersion(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $directGroups = $this->directGroupPayload($user);

        return [
            'is_administrator' => $user->is_administrator,
            'session_permissions' => $this->sessionPermissions(),
            'permissions_table' => $this->tableVersion('permissions'),
            'direct_user_permissions' => $this->assignablePermissionVersion(User::class, [$user->id]),
            'direct_groups' => $directGroups['ids'],
            'direct_group_memberships' => $directGroups['version'],
            'direct_group_permissions' => $this->assignablePermissionVersion(
                Group::class,
                $directGroups['ids']
            ),
        ];
    }

    /**
     * Include the current session permission snapshot used by legacy permission checks.
     */
    private function sessionPermissions(): array
    {
        $permissions = session('permissions', []);

        if (!is_array($permissions)) {
            return [];
        }

        sort($permissions);

        return $permissions;
    }

    /**
     * Include the default Tasks saved search when the Saved Search package is installed.
     */
    private function savedSearchPayload(?User $user): ?array
    {
        $class = 'ProcessMaker\\Package\\SavedSearch\\Models\\SavedSearch';
        if (!$user || !class_exists($class)) {
            return null;
        }

        $savedSearch = $class::firstSystemSearchFor($user, $class::KEY_TASKS);
        if (!$savedSearch) {
            return null;
        }

        return [
            'id' => $savedSearch->id,
            'updated_at' => $this->dateValue($savedSearch->updated_at),
            'columns_hash' => $this->hashValue($savedSearch->columns),
        ];
    }

    /**
     * Capture the user UI configuration rendered into Tasks page props.
     */
    private function userConfigurationPayload(?User $user): array
    {
        if (!$user) {
            return UserConfigurationController::DEFAULT_USER_CONFIGURATION;
        }

        $configuration = UserConfiguration::select('updated_at', 'ui_configuration')
            ->where('user_id', $user->id)
            ->first();
        if (!$configuration) {
            return [
                'updated_at' => null,
                'ui_configuration_hash' => $this->hashValue(UserConfigurationController::DEFAULT_USER_CONFIGURATION),
            ];
        }

        return [
            'updated_at' => $this->dateValue($configuration->updated_at),
            'ui_configuration_hash' => $this->hashValue($configuration->ui_configuration),
        ];
    }

    /**
     * Return direct group ids and their version marker without loading group models.
     */
    private function directGroupPayload(User $user): array
    {
        $memberships = DB::table('group_members')
            ->where('member_type', User::class)
            ->where('member_id', $user->id)
            ->orderBy('group_id')
            ->get(['group_id', 'updated_at']);

        return [
            'ids' => $memberships->pluck('group_id')->all(),
            'version' => [
                'count' => $memberships->count(),
                'updated_at' => $this->dateValue($memberships->max('updated_at')),
            ],
        ];
    }

    /**
     * Hash direct permission assignment ids for an assignable type.
     */
    private function assignablePermissionVersion(string $assignableType, array $assignableIds): array
    {
        if (empty($assignableIds)) {
            return [
                'count' => 0,
                'permission_ids_hash' => $this->hashValue([]),
            ];
        }

        $permissionIds = DB::table('assignables')
            ->where('assignable_type', $assignableType)
            ->whereIn('assignable_id', $assignableIds)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->all();

        return [
            'count' => count($permissionIds),
            'permission_ids_hash' => $this->hashValue($permissionIds),
        ];
    }

    /**
     * Version a table with a compact count and updated_at marker.
     */
    private function tableVersion(string $table): array
    {
        $version = DB::table($table)
            ->selectRaw('COUNT(*) as count, MAX(updated_at) as updated_at')
            ->first();

        return [
            'count' => (int) ($version->count ?? 0),
            'updated_at' => $this->dateValue($version->updated_at ?? null),
        ];
    }

    /**
     * Hash structured values before placing them in the ETag payload.
     */
    private function hashValue($value): string
    {
        return hash($this->hashAlgorithm(), json_encode($value));
    }

    /**
     * Capture selected config values and frontend asset versions used by the page shell.
     */
    private function featuresPayload(): array
    {
        $features = [];
        foreach (self::FEATURE_CONFIG_KEYS as $key) {
            Arr::set($features, $key, config($key));
        }

        $features['mix_manifest'] = $this->fileVersion(public_path('mix-manifest.json'));

        return $features;
    }

    /**
     * Version installed package state so package-provided UI changes invalidate the page.
     */
    private function packagesPayload(): array
    {
        $packages = app(PackageManager::class)->listPackages();
        sort($packages);

        $manifest = app(PackageManifest::class);

        return [
            'app_version' => $this->appVersion(),
            'registered' => $packages,
            'manifest' => method_exists($manifest, 'list') ? $manifest->list() : $manifest->providers(),
            'composer_lock' => $this->fileVersion(base_path('composer.lock')),
        ];
    }

    /**
     * Read the ProcessMaker application version from composer metadata.
     */
    private function appVersion(): ?string
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        return $composer['version'] ?? null;
    }

    /**
     * Return a cheap version marker for files that affect rendered assets or packages.
     */
    private function fileVersion(string $path): ?array
    {
        if (!File::exists($path)) {
            return null;
        }

        return [
            'mtime' => File::lastModified($path),
            'hash' => hash_file($this->hashAlgorithm(), $path),
        ];
    }

    /**
     * Prefer xxh128 when available and fall back for older runtimes.
     */
    private function hashAlgorithm(): string
    {
        return in_array('xxh128', hash_algos(), true) ? 'xxh128' : 'sha256';
    }

    /**
     * Normalize nullable date values for deterministic JSON hashing.
     */
    private function dateValue($value): ?string
    {
        return $value ? (string) $value : null;
    }

    /**
     * Recursively sort arrays so query-string order does not change the ETag.
     */
    private function sorted(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sorted($item);
            }
        }

        return $value;
    }
}
