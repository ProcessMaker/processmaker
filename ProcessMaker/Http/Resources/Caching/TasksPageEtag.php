<?php

namespace ProcessMaker\Http\Resources\Caching;

use Illuminate\Foundation\PackageManifest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use ProcessMaker\Filters\SaveSession;
use ProcessMaker\Helpers\DefaultColumns;
use ProcessMaker\Http\Controllers\Api\UserConfigurationController;
use ProcessMaker\Managers\PackageManager;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use ProcessMaker\Models\UserConfiguration;
use ProcessMaker\Services\PermissionServiceManager;

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
                'default_columns' => DefaultColumns::get('tasks'),
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
     */
    private function permissionsVersion(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $permissions = $user->is_administrator
            ? Permission::query()->pluck('name')->all()
            : app(PermissionServiceManager::class)->getUserPermissions($user->id);
        sort($permissions);

        return [
            'is_administrator' => $user->is_administrator,
            'permissions' => $permissions,
            'session_permissions' => $this->sessionPermissions(),
            'groups_updated_at' => $this->dateValue(
                GroupMember::where('member_type', User::class)
                    ->where('member_id', $user->id)
                    ->max('updated_at')
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
            'columns' => $savedSearch->columns,
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

        $configuration = UserConfiguration::where('user_id', $user->id)->first();
        if (!$configuration) {
            return [
                'updated_at' => null,
                'ui_configuration' => UserConfigurationController::DEFAULT_USER_CONFIGURATION,
            ];
        }

        return [
            'updated_at' => $this->dateValue($configuration->updated_at),
            'ui_configuration' => $configuration->ui_configuration,
        ];
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
