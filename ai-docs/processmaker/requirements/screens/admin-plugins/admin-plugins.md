# Admin plugins screen

Administrative screen for listing and managing ProcessMaker plugins.

## Purpose

Give operators with `manage-plugins` a UI to list plugins, start install from a repository URL (branch or tag), uninstall, and toggle enable/disable, while showing live install output.

## Kind and reuse

- `kind`: screen
- Depends on `plugin-manager` for lifecycle behavior; does not redefine install/uninstall FRs
- Progress UI listens to `PluginLog` on the authenticated user's private channel

## Related assets

- `resources/views` admin plugins entry
- `resources/js/admin/plugins/Plugins.vue`
- `resources/js/admin/plugins/components/PluginOutput.vue`
- Web route `plugins.index`, API routes under `api/1.0/plugins*`

## Assumptions

- Horizon (or equivalent queue workers) is running so queued plugin jobs execute.
- Echo / broadcasting is available so `PluginLog` updates appear in `PluginOutput`.
- The screen does not wait for clone/composer to finish on the HTTP response; it stays open until a done or error progress event.
