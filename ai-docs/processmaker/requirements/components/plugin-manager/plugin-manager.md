# Plugin manager

Lifecycle management for ProcessMaker plugins stored under the tenant plugins directory.

## Purpose

Allow authorized operators to install plugins from a Git repository URL or zip archive, uninstall them, enable/disable them, and observe progress without blocking the HTTP request.

## Kind and reuse

- `kind`: component
- Configurable via `config` in `requirements.yaml` (sources, progress channel, queue settings)
- Admin UI screens compose this component through `dependsOn`

## Actors

- Operator with `manage-plugins` permission
- Queue worker (Horizon) executing plugin jobs
- Runtime refresh after plugin code changes (route cache, worker restart)

## Related assets

- `ProcessMaker/Managers/PluginManager.php`
- `ProcessMaker/Jobs/InstallPlugin.php`, `UninstallPlugin.php`, `TogglePlugin.php`
- `ProcessMaker/Http/Controllers/Api/PluginController.php`
- `ProcessMaker/Events/PluginLog.php`
- `resources/js/admin/plugins/`

## Configuration

Consumers configure allowed install sources (`url`, `zip`), optional Git `branch`/`tag` (mutually exclusive), progress broadcast target (authenticated user channel), and job timeout / overlap policy for concurrent plugin operations.
