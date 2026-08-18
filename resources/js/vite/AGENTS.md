# Vite views (ProcessMaker)

Guide for humans and coding agents working under Vite Blade layouts and the parallel Vite asset pipeline.

Vite runs **alongside** Laravel Mix. Do not replace Mix globally. Migrate routes one at a time.

## Mental model

| Pipeline | Owns | Blade helper | Dev server / output |
|----------|------|--------------|---------------------|
| **Mix** | Most of the app (`public/js`, `public/css`) | `mix('js/...')`, `mix('css/...')` | `npm run development` / `watch` → `public/` + `mix-manifest.json` |
| **Vite** | Page entries (under `resources/js/vite`, feature folders, or `jscomposition`), shared Sass / translations | `@vite([...])` | `npm run vite:dev` → `:5173` + `storage/vite.hot`; `npm run vite:build` → `public/build` + manifest |

- Mix HMR file (if used): `public/hot` — **reserved for Mix**. Never point Vite hot here.
- Vite hot file: `storage/vite.hot` (configured in `vite.config.js` and `Vite::useHotFile()` in `ProcessMakerServiceProvider`).
- If Vite wrote `public/hot`, `mix('js/...')` would rewrite Mix URLs to `http://127.0.0.1:5173/...` and 404 (e.g. `typeForm.js`).

## Layouts

| Layout | CSS / JS | Use when |
|--------|----------|----------|
| `layouts.layout` / `layouts.layoutnext` | Mix CSS/JS chrome | Legacy Mix pages (e.g. catalogue mobile) |
| `layouts.layoutnextvite` | `@vite` of `app.scss`, `sidebar`, `collapseDetails`, `tailwind.css` | Authenticated Vite pages (Tasks, Processes Designer, Processes Catalogue desktop, Cases, Admin…) |
| `layouts.mobilenextvite` | Vite-based wrapper around `layoutnextvite` with mobile chrome | Mobile authenticated pages such as request detail |
| Standalone / `auth.layouts.auth` | Page-level `@vite` | Login and password-reset style pages |

File: `resources/views/layouts/layoutnextvite.blade.php` (must be `.blade.php`).

**Script order in `layoutnextvite`:** `@yield('js')` runs **before** package addon scripts (`$addons` / `GlobalScripts`). Package code that mutates `window.ProcessMaker.*` after yield may not be ready in a page module’s first tick. Prefer `window.addEventListener('load', …)` when merging package tabs/addons (see Customize UI). Classic `layout` loads addons **before** `@yield('js')`.

## How routes switch to Vite (current pattern)

There is **no** `VITE_VIEW` env map. Migration is done **in place** on the existing Blade:

1. Change `@extends(...)` to `layouts.layoutnextvite` (in-app) or keep standalone/auth layout.
2. Replace `mix('js/...')` page bootstraps with `@vite([...])`.
3. Register the JS/CSS entry in `vite.config.js` → `laravel({ input: [...] })`.
4. Keep Mix-built package scripts as classic `<script src="...">` when packages still ship Mix assets (`$manager->getScripts()`, `GlobalScripts`, etc.).

Entries may live under:

- `resources/js/vite/<area>/` (Tasks, auth)
- Feature folder next to the UI (e.g. `resources/js/admin/`, `resources/js/processes-catalogue/`)
- Composition tree (e.g. `resources/jscomposition/cases/casesMain/`)

Prefer co-locating the Vite entry with the feature source when that tree already owns the page.

Shared admin chrome: `resources/js/admin/loaderAdmin.js` (`setupMain` + packages) is used by Script Executors, Tenant Queues, DevLink, Cases Retention, and Logs.

## Folder layout

```
resources/views/layouts/layoutnextvite.blade.php
resources/views/tasks/index.blade.php                 ← Vite
resources/views/tasks/editMobile.blade.php             ← Vite (mobile task edit)
resources/views/tasks/preview.blade.php               ← Vite standalone (iframe)
resources/views/notifications/index.blade.php         ← Vite
resources/views/templates/import.blade.php            ← Vite
resources/views/templates/configure.blade.php         ← Vite
resources/views/templates/assets.blade.php            ← Vite (export-screen/import-screen/list still Mix)
resources/views/processes/index.blade.php             ← Vite (Designer /processes)
resources/views/processes/edit.blade.php              ← Vite (Configure Process)
resources/views/processes/list.blade.php              ← mounts @vite processes.js (@append)
resources/views/processes-catalogue/index.blade.php   ← Vite (desktop)
resources/views/cases/casesMain.blade.php             ← Vite
resources/views/cases/edit.blade.php                  ← Vite (case detail)
resources/views/auth/newLogin.blade.php               ← Vite
resources/views/auth/layouts/auth.blade.php           ← Vite scripts (reset/email)
resources/views/admin/script-executors/index.blade.php ← Vite
resources/views/admin/tenant-queues/index.blade.php   ← Vite
resources/views/admin/queues/index.blade.php          ← Vite layout (Horizon iframe; no page app)
resources/views/admin/settings/ldap-logs.blade.php    ← Vite
resources/views/admin/devlink/index.blade.php         ← Vite
resources/views/admin/cases-retention/index.blade.php ← Vite
resources/views/admin/logs/index.blade.php            ← Vite
resources/views/processes/environment-variables/index.blade.php ← Vite
resources/views/processes/environment-variables/edit.blade.php  ← Vite
resources/views/processes/screens/index.blade.php       ← Vite (Screens listing tabs)
resources/views/processes/screens/edit.blade.php        ← Vite (Configure Screen)
resources/views/processes/modeler/index.blade.php       ← Vite (process modeler)
resources/views/requests/show.blade.php                 ← Vite (request detail)
resources/views/requests/showMobile.blade.php           ← Vite (mobile request detail)
resources/js/vite/tasks/                              ← Tasks entries
resources/js/vite/auth/login.js                       ← Login / auth layout entry
resources/js/admin/loaderAdmin.js                     ← shared admin setupMain loader
resources/js/processes/environment-variables/loaderEnvironment.js ← env vars setupMain loader
resources/js/processes/screens/loaderScreens.js       ← screens setupMain loader
resources/js/processes-catalogue/loaderProcessesCatalogue.js
resources/jscomposition/cases/casesMain/loaderCasesMain.js
resources/js/translations/index.js                    ← shared i18n Vite entry
resources/sass/*.scss|css                             ← also compiled by Vite for layoutnextvite
vite.config.js
```

## Current migrations

| Route area | Status | View | JS entry(ies) |
|------------|--------|------|----------------|
| Tasks inbox | **Vite** | `tasks.index` + `layoutnextvite` | `vite/tasks/loaderTasks.js` → ScreenBuilder scripts → `vite/tasks/tasks.js` |
| Task edit | **Vite** | `tasks.edit` + `layoutnextvite` | `tasks/loaderEdit.js` → `tasks/edit.js` + inline Vue on `load` |
| Mobile task edit | **Vite** | `tasks.editMobile` + `mobilenextvite` | `tasks/loaderTasks.js` → deferred package scripts → `tasks/show.js` + inline Vue mount on `load` |
| Task preview (iframe) | **Vite** | `tasks.preview` — **standalone** (no layout) | `tasks/loaderPreview.js` → `tasks/preview.js` |
| Task show | **Vite** | `tasks.show` + `layoutnextvite` | `tasks/loaderTasks.js` → `tasks/show.js` |
| Inbox Rules | **Vite** | `inbox-rules.index` + `layoutnextvite` | `inbox-rules/index.js` |
| About | **Vite** | `about.index` + `layoutnextvite` | layout change only; no page JS entry |
| Profile edit | **Vite** | `profile.edit` + `layoutnextvite` | `admin/profile/loaderProfile.js` → `admin/profile/edit.js` |
| Requests index | **Vite** | `requests.index` + `layoutnextvite` | `requests/loaderRequests.js` → `requests/index.js` |
| Request detail | **Vite** | `requests.show` + `layoutnextvite` | `requests/loaderRequestsShow.js` → modeler `initialLoad.js` + `requests/show.js` → inline Vue mount on `window` `load` |
| Mobile request detail | **Vite** | `requests.showMobile` + `mobilenextvite` | `requests/loaderRequestsShow.js` + `requests/show.js` → inline Vue mount on `window` `load` |
| Notifications | **Vite** | `notifications.index` + `layoutnextvite` | `notifications/loaderNotifications.js` → `notifications/index.js` |
| Template Import | **Vite** | `templates.import` + `layoutnextvite` | `templates/loaderTemplates.js` → `templates/import/index.js` (Vue Router) |
| Template Configure | **Vite** | `templates.configure` + `layoutnextvite` | boot `window.temporal.templateConfigurations` → `templates/loaderTemplates.js` → `templates/configure.js` + `mixins: addons` |
| Template Assets | **Vite** | `templates.assets` + `layoutnextvite` | `templates/loaderTemplates.js` → `templates/assets.js` (state from `localStorage`) |
| Processes (Designer) | **Vite** | `processes.index` + `layoutnextvite`; apps via child `@append` | `processes/loaderProcesses.js` → `processes.js` / `templates` / `categories` / `archived` |
| Designer home | **Vite** | `designer.index` + `layoutnextvite` | `processes/loaderProcesses.js` → `newDesigner.js` |
| Process Export | **Vite** | `processes.export` + `layoutnextvite` | `processes/loaderProcesses.js` → `export/index.js` (Vue Router) |
| Process Import | **Vite** | `processes.import` + `layoutnextvite` | `processes/loaderProcesses.js` → `import/index.js` (Vue Router) |
| Process Configure | **Vite** | `processes.edit` + `layoutnextvite` | `processes/loaderProcesses.js` → `processes/edit.js` + inline Vue boot |
| Admin Users | **Vite** | `admin.users.index` + `edit` + `layoutnextvite` | `admin/users/loaderUsers.js` → `index.js` / `edit.js` + inline Vue boot |
| Admin Groups | **Vite** | `admin.groups.index` + `edit` + `layoutnextvite` | `admin/groups/loaderGroups.js` → `index.js` / `edit.js` + inline Vue boot |
| Admin Auth Clients | **Vite** | `auth-clients.index` + `layoutnextvite` | `admin/auth-clients/loaderAuthClients.js` → `index.js` |
| Admin Settings | **Vite** | `settings.index` + `layoutnextvite` | `admin/settings/loaderSettings.js` → `index.js` (+ optional package email-listener Mix) |
| Admin LDAP Logs | **Vite** | `admin.settings.ldap-logs` + `layoutnextvite` | `admin/users/loaderUsers.js` → `admin/settings/ldaplogs.js` |
| Admin Customize UI | **Vite** | `customize-ui.edit` + `layoutnextvite` | `admin/cssOverride/loaderCssOverride.js` → `edit.js` (Tinymce) + inline Vue on `load` |
| Admin Script Executors | **Vite** | `script-executors.index` + `layoutnextvite` | `admin/loaderAdmin.js` → `admin/script-executors/index.js` |
| Admin Tenant Queues | **Vite** | `tenant-queue.index` + `layoutnextvite` | `admin/loaderAdmin.js` → `admin/tenant-queues/index.js` (Vue Router) |
| Admin Queues (Horizon) | **Vite layout** | `admin.queues.index` + `layoutnextvite` | `admin/users/loaderUsers.js` only; page is an iframe to `/admin/horizon` |
| Admin DevLink | **Vite** | `devlink.index` + `layoutnextvite` | `admin/loaderAdmin.js` → `admin/devlink/index.js` (Vue Router) |
| Admin Cases Retention | **Vite** | `cases-retention.index` + `layoutnextvite` | `admin/loaderAdmin.js` → `admin/cases-retention/index.js` |
| Admin Logs | **Vite** | `admin.logs` + `layoutnextvite` | packages boot → `admin/loaderAdmin.js` → `admin/logs/index.js` (Vue Router) |
| Environment Variables | **Vite** | `environment-variables.index` + `edit` + `layoutnextvite` | `processes/environment-variables/loaderEnvironment.js` → `index.js` / `edit.js` |
| Screens (Designer) | **Vite** | `screens.index` + `edit` + `layoutnextvite`; tab apps via child `@append` | `processes/screens/loaderScreens.js` → `screens/index.js` / `screen-templates/myTemplates.js` / `publicTemplates.js` / `categories/index.js`; edit → `screens/edit.js` |
| Scripts (Designer) | **Vite** | `scripts.index` + `scripts.edit` (configure) + `layoutnextvite` | `processes/scripts/loaderScripts.js` → `index.js`; configure → `editConfig.js` + inline Vue on `load` |
| Signals (Designer) | **Vite** | `signals.index` + `signals.edit` + `layoutnextvite` | `processes/signals/loaderSignals.js` → `index.js`; edit → `edit.js` + inline Vue on `load` |
| Modeler | **Vite** | `processes.modeler.index` + `layoutnextvite` | `modeler/loaderModeler.js` (imports `initialLoad.js`) → package Mix scripts → `leave-warning.js` → `modeler/index.js` on `load` |
| Processes Catalogue (desktop) | **Vite** | `process.browser.index` (`/process-browser`) + `layoutnextvite` | `processes-catalogue/loaderProcessesCatalogue.js` → ScreenBuilder scripts → `processesCatalogue.js` |
| Cases | **Vite** | `cases.casesMain` (`/cases`) + `layoutnextvite` | `jscomposition/.../loaderCasesMain.js` → GlobalScripts / ScreenBuilder → `casesMain.js` |
| Case Detail | **Vite** | `cases.edit` + `layoutnextvite` | `jscomposition/.../loaderCasesDetail.js` → `initialLoad` (Vite) + GlobalScripts / modeler scripts → `casesDetail.js` |
| Login | **Vite** | `LOGIN_VIEW` / `auth.newLogin` | Head: `app.scss` + `vite/auth/login.js`; footer: GlobalScripts (skip dynamic-ui) + `translations/index.js` |
| Auth layout (reset / email) | **Vite scripts** | `auth.layouts.auth` | `vite/auth/login.js` → GlobalScripts (skip dynamic-ui) → packages boot → `translations/index.js` |
| Processes Catalogue (mobile) | Mix | `processes-catalogue/mobile.blade.php` | Mix catalogue / mobile bundle |

## Registered Vite inputs (`vite.config.js`)

```
resources/js/vite/auth/login.js
resources/js/translations/index.js
resources/js/vite/tasks/loaderTasks.js
resources/js/vite/tasks/tasks.js
resources/js/tasks/loaderEdit.js
resources/js/tasks/edit.js
resources/js/tasks/loaderPreview.js
resources/js/tasks/preview.js
resources/js/tasks/loaderTasks.js
resources/js/tasks/show.js
resources/js/inbox-rules/index.js
resources/js/processes/loaderProcesses.js
resources/js/processes/processes.js
resources/js/processes/edit.js
resources/js/processes/newDesigner.js
resources/js/processes/export/index.js
resources/js/processes/import/index.js
resources/js/templates/index.js
resources/js/processes/categories/index.js
resources/js/processes/archived.js
resources/js/admin/loaderAdmin.js
resources/js/admin/users/loaderUsers.js
resources/js/admin/users/index.js
resources/js/admin/users/edit.js
resources/js/admin/groups/loaderGroups.js
resources/js/admin/groups/index.js
resources/js/admin/groups/edit.js
resources/js/admin/auth-clients/loaderAuthClients.js
resources/js/admin/auth-clients/index.js
resources/js/admin/settings/loaderSettings.js
resources/js/admin/settings/index.js
resources/js/admin/settings/ldaplogs.js
resources/js/admin/cssOverride/loaderCssOverride.js
resources/js/admin/cssOverride/edit.js
resources/js/admin/script-executors/index.js
resources/js/admin/tenant-queues/index.js
resources/js/admin/devlink/index.js
resources/js/admin/cases-retention/index.js
resources/js/admin/logs/index.js
resources/js/admin/profile/loaderProfile.js
resources/js/admin/profile/edit.js
resources/js/requests/loaderRequests.js
resources/js/requests/loaderRequestsShow.js
resources/js/requests/index.js
resources/js/notifications/loaderNotifications.js
resources/js/notifications/index.js
resources/js/templates/loaderTemplates.js
resources/js/templates/import/index.js
resources/js/templates/configure.js
resources/js/templates/assets.js
resources/js/processes/environment-variables/loaderEnvironment.js
resources/js/processes/environment-variables/index.js
resources/js/processes/environment-variables/edit.js
resources/js/processes/screens/loaderScreens.js
resources/js/processes/screens/index.js
resources/js/processes/screen-templates/myTemplates.js
resources/js/processes/screen-templates/publicTemplates.js
resources/js/processes/screens/edit.js
resources/js/processes/scripts/loaderScripts.js
resources/js/processes/scripts/index.js
resources/js/processes/scripts/editConfig.js
resources/js/processes/scripts/edit.js
resources/js/processes/modeler/loaderModeler.js
resources/js/processes/modeler/index.js
resources/js/processes/modeler/initialLoad.js
resources/js/processes/modeler/loaderInflight.js
resources/js/processes/modeler/process-map.js
resources/js/process-map-layout.js
resources/js/processes/signals/loaderSignals.js
resources/js/processes/signals/index.js
resources/js/processes/signals/edit.js
resources/js/processes-catalogue/loaderProcessesCatalogue.js
resources/js/processes-catalogue/processesCatalogue.js
resources/jscomposition/cases/casesMain/loaderCasesMain.js
resources/jscomposition/cases/casesMain/casesMain.js
resources/jscomposition/cases/casesDetail/loaderCasesDetail.js
resources/jscomposition/cases/casesDetail/casesDetail.js
resources/js/initialLoad.js
resources/sass/app.scss
resources/sass/sidebar/sidebar.scss
resources/sass/collapseDetails.scss
resources/sass/tailwind.css
```

## npm scripts

```bash
npm run vite:dev      # HMR; writes storage/vite.hot
npm run vite:build    # production assets in public/build
npm run development   # Mix only
npm run production    # Mix --production && vite build
npm run build:all     # Mix then Vite build
```

### Mix / webpack note

Laravel Mix 6 needs Webpack **5.88.x**. Newer Webpack (e.g. 5.109) removes `SizeFormatHelpers` and breaks Mix with:

`Cannot find module 'webpack/lib/SizeFormatHelpers'`

This repo pins Webpack via `package.json` → `"overrides": { "webpack": "5.88.2" }`.

Dev tips:

- Port `5173` must be free (`strictPort: true`).
- App may be opened as `127.0.0.1`, `localhost`, or LAN IP; Vite origin is `http://127.0.0.1:5173` with CORS reflecting the page origin.
- Opening the app from **another machine** via LAN IP will not load Vite assets (browser would hit *that* machine’s `127.0.0.1:5173`).

## Checklist: transition a route to Vite

1. **Blade**
   - Edit the existing Mix Blade in place.
   - Prefer `@extends('layouts.layoutnextvite')` for in-app pages.
   - Load page JS with `@vite([...])`.
   - Keep Mix package scripts as classic tags / `$manager->getScripts()` / `GlobalScripts` when required.
   - Prefer a **small surface**: swap layout + replace the primary Mix page script. Avoid inventing `$vite` flags on every shared child partial unless those children must stay Mix-only on other routes.
   - **Script order:**
     1. Inline boot (`window.temporal = {...}`; set `window.packages` if `setupMain` needs it)
     2. `@vite` loader (`setupMain` + ScreenBuilder as needed)
     3. Package scripts (`defer` — after yield in `layoutnextvite`)
     4. `@vite` page app (often via `@section('js')` / `@append` from a child Blade)

2. **Blade ↔ Vue bindings (common failure mode)**
   - `__()` is PHP. Vue never runs it. In a Vue attribute it must be executed by Blade.
   - Strings for `:prop` → `:type="{{ Js::from(__('Process')) }}"` (JSON-quoted string Vue can evaluate).
   - Plain text props → `type="{{ __('Process') }}"` (no `:`).
   - Booleans / numbers / arrays → always bind with `:` and `@json(...)` (or `Js::from`):
     - Good: `:is-ab-testing-installed="@json(...)"` → Vue gets `true`/`false`
     - Bad: `is-ab-testing-installed="{{ ... }}"` → PHP `true` becomes string `"1"` → Vue prop type warning
   - Do **not** use `@{{ __('...') }}` for server translations (`@{{` is for Vue interpolations in the template).
   - Root Vue on a Blade `el` (e.g. `#editCss`): directives in that Blade (`v-if="showTabs"`) bind to the Vue instance mounted on that `el`. Blade does not “pass” the variable; Vue owns the DOM after mount.

3. **JS entry**
   - Vue 2 Options API; `import Vue from 'vue'` / `import VueRouter from 'vue-router'` in ESM.
   - Register in `vite.config.js` → `laravel({ input: [...] })`.
   - `.vue` extension optional (`resolve.extensions` includes `.vue`); prefer explicit `.vue` imports.
   - Mirror `window.ProcessMaker` **and** `window.Processmaker` when legacy mixins expect the lowercase `m`.
   - Prefer reading Blade boot data from `window.temporal` / `window.ProcessMaker` (ESM cannot see Blade `const` / `let`).
   - Avoid circular ESM: do not import feature `EventBus` from a Mix page entry that also imports the feature graph.
   - Multi-tab pages (e.g. Designer Processes): one Vite page entry may mount **all** tab roots (`#processIndex`, `#templatesIndex`, `#categories-listing`, `#archivedProcess`) instead of one Mix file per tab.
   - Register globals the child Blades expect (`Required`, listing components) in that same entry when Mix chrome no longer does it.
   - Do **not** double-mount the same `el` from two entries (first mount strips Vue directives; second remount leaves dead UI).

4. **Mix cleanup**
   - Remove the migrated Mix input from `webpack.mix.js` when no Blade still calls `mix('js/...')` for it.
   - Leave Mix entries that other routes / shared child Blades still load (scripts, modeler, screens preview, etc.).

5. **Controller**
   - Usually no change if the Blade name stays the same.
   - Pass the same view data the page already expected.

6. **Verify**
   - With `vite:dev`: Network shows `127.0.0.1:5173` for `@vite` assets; Mix URLs stay on the app host.
   - Without Vite running: need a prior `vite:build` (manifest).
   - `mix('...')` must not point at `:5173` (delete stale `public/hot` if it does).
   - Check Vue console for prop type warnings (`Expected Boolean, got String "1"`) after migrating Blade attrs.
   - **PHPUnit:** `Tests\TestCase` calls `withoutVite()` so `@vite` Blades do not need `public/build/manifest.json`. Feature tests only see server HTML (JS-mounted DOM is not executed). Mix helpers still need `mix-manifest.json` if the Blade still calls `mix()`.

7. **Do not**
   - Alias `@vite` in `vite.config.js` (shadows `@vite/client` / `@vite/env`).
   - Point Vite `hotFile` at `public/hot`.
   - Include `auth.partials.auth-language-scripts` on Vite auth pages (still references dead Mix `builds/login/js/...` → `MixFileNotFoundException`).
   - Leave debug `console.log` in boot Blades (e.g. `cases/edit` `temporal` dumps).
   - Put `resources/js/**/*.{js,vue}` in Laravel Vite `refresh` if you want component HMR (that forces full page reload).

## Known Vite config notes (`vite.config.js`)

- **`resolve.extensions`**: includes `.vue`. If both `Foo.js` and `Foo.vue` exist, `.js` wins.
- **Styles alias**: `styles` / `~styles` → `resources/sass`; Sass importer mirrors Webpack `~styles/...`.
- **Tailwind**: entry `resources/sass/tailwind.css` + root `postcss.config.js`.
- **Fonts**: Sass `$FontPathOpenSans` / `$fa-font-path` are root-relative (`/css/precompiled/...`). During `vite:dev`, `server.proxy['/css']` forwards to `APP_URL` (avoids CORS).
- **Vue2 plugin**: `includeAbsolute: false` so `/img/...` stays Laravel public URLs. Enables **component HMR** for `.vue` SFCs.
- **`refresh`**: `resources/views/**`, `resources/sass/**` → **full page reload** when those change. Do not list page JS/Vue here if you want HMR.
- **`server.hmr`**: WebSocket host/protocol only; does not choose full vs component reload.
- **YAML plugin**: `@rollup/plugin-yaml`.
- **Modeler**: `modelerPublicPathPlugin` rewrites `@processmaker/modeler` Webpack-style `img/` URLs to `/js/img/`; `optimizeDeps.exclude: ['@processmaker/modeler']` so that transform runs.

## Quick reference

**Processes (Designer)** — `/processes`

- View: `resources/views/processes/index.blade.php` → `layoutnextvite`
- Loader: `@vite(['resources/js/processes/loaderProcesses.js'])`
- Page apps via child `@append`: `processes.js`, `templates/index.js`, `categories/index.js`, `archived.js` (one mount each — avoid double-mounting the same `el`)
- `loaderProcesses.js`: `setupMain()` + copy `window.temporal?.packages` onto `ProcessMaker.packages` / `window.packages`
- Mix: `webpack.mix.js` no longer builds `resources/js/processes/index.js` / `edit.js` / `newDesigner.js`. Other Designer Mix bundles (script preview, modeler inflight `process-map.js`, Mix `initialLoad.js` for Mix pages) remain.

**Designer home** — `/designer`

- View: `resources/views/designer/index.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` + `window.Processmaker.user` before loader
- Entries: `loaderProcesses.js` → `newDesigner.js` (mounts `#new-designer`)
- Boolean Blade props (`project`, `is-documenter-installed`) use `:prop="@json(...)"`

**Process Export** — `/processes/{process}/export`

- View: `resources/views/processes/export.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Entries: `loaderProcesses.js` → `export/index.js` (uses `window.ProcessMaker.Router`, meta tags for process name / project id)

**Process Import** — `/processes/import`

- View: `resources/views/processes/import.blade.php` → `layoutnextvite`
- Boot: packages + `ProcessMaker.importIsRunning` / `queueImports` (booleans via `@json`) before loader
- Entries: `loaderProcesses.js` → `import/index.js` (Vue Router; shares export `State` / `CustomExportView`)

**Process Configure** — `/processes/{process}/edit`

- View: `resources/views/processes/edit.blade.php` → `layoutnextvite`
- Boot: set `window.temporal.packages` / `window.packages` before loader
- Entries: `loaderProcesses.js` → `edit.js` (registers `CategorySelect`, `ProcessesPermissions`)
- Vue root stays **inline** in the Blade (`window.addEventListener('load', …)`) so Blade `@json(...)` boot data and plugin `mixins: addons` keep working without a full ESM rewrite
- Plugin addons still come from `layoutnextvite` (`var addons = []` + `script` / `script_mix`)

**Scripts (Designer)** — `/designer/scripts`, `/designer/scripts/{script}/edit` (configure)

- Views: `processes/scripts/index.blade.php`, `processes/scripts/edit.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Index: `loaderScripts.js` → `index.js` (via categorized resource / list)
- Configure: `loaderScripts.js` → `editConfig.js` (registers `CategorySelect`, `SliderWithInput`) + inline Vue on `load` (`mixins: addons`)
- Mix still builds script **builder** (`edit.js`) and **preview** (`preview.js`)

**Signals (Designer)** — `/designer/signals`, `/designer/signals/{signalId}/edit`

- Views: `processes/signals/index.blade.php`, `processes/signals/edit.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Index: `loaderSignals.js` → `index.js` (mounts `#listSignals` with create modal + listing)
- Edit: `loaderSignals.js` → `edit.js` (registers `CatchListing`) + inline Vue on `load` (`mixins: addons`)

**Admin Users** — `/admin/users`, `/admin/users/{user}/edit`

- Views: `admin/users/index.blade.php`, `admin/users/edit.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Index: `loaderUsers.js` → `index.js` (mounts listings; `window.loadUsers` / `loadDeletedUsers`)
- Edit: `loaderUsers.js` → `edit.js` (registers password/listing components) + inline Vue on `load` (`modalVueInstance` + `formVueInstance`, `mixins: addons`)
- Mix: no longer builds `admin/users/index.js` or `edit.js`

**Admin Groups** — `/admin/groups`, `/admin/groups/{group}/edit`

- Views: `admin/groups/index.blade.php`, `admin/groups/edit.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Index: `loaderGroups.js` → `index.js` (registers `GroupsListing` + mounts `#listGroups`)
- Edit: `loaderGroups.js` → `edit.js` (registers listing/select components) + inline Vue on `load` (`mixins: addons`, Blade `@json`)
- Mix: no longer builds `admin/groups/index.js` or `edit.js`

**Admin Auth Clients** — `/admin/auth-clients`

- View: `resources/views/admin/auth-clients/index.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Entries: `loaderAuthClients.js` → `index.js` (registers `AuthClientsListing` + mounts `#authClients`)
- Mix: no longer builds `admin/auth-clients/index.js`

**Admin Settings** — `/admin/settings`

- View: `resources/views/admin/settings/index.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before loader
- Entries: `loaderSettings.js` → optional Mix `email-listener.js` (package-email-start-event) → `index.js` (mounts `#settings` / `SettingsMain`)
- Plugin addon HTML still rendered in content; addon scripts via `layoutnextvite`
- Mix: no longer builds `admin/settings/index.js`

**Admin LDAP Logs** — settings LDAP logs

- View: `resources/views/admin/settings/ldap-logs.blade.php` → `layoutnextvite`
- Entries: `admin/users/loaderUsers.js` → `admin/settings/ldaplogs.js`
- Mix: no longer builds `admin/settings/ldaplogs.js`

**Admin Customize UI** — `/admin/customize-ui/{tab?}`

- View: `resources/views/admin/cssOverride/edit.blade.php` → `layoutnextvite`
- Boot: packages + `window.config` / `loginFooterSetting` / `altTextSetting` before loader (ESM-safe for `SiteDesign.vue`)
- Entries: `loaderCssOverride.js` → `edit.js` (registers Tinymce + `SiteDesign` / `ColorPicker`) + **inline Vue on `load`** (`#editCss`, `showTabs` / package `cssOverrideTabs`)
- TinyMCE under Vite: import core + theme + `icons/default` + plugins; with `skin: false` also import `tinymce/skins/ui/oxide/skin.min.css`. Content iframe CSS: inject oxide/default content CSS via `content_style` (`?raw`) plus `content_css: '/css/app.css'` — parent-page CSS imports do not apply inside the editor iframe.
- Package tabs: `showTabs` is `tabs.length > 1`. `cssOverrideTabs` may arrive after page modules because addon scripts load after `@yield('js')`; merge on `window` `load` when needed.
- Mix: no longer builds `admin/cssOverride/edit.js`

**Admin Script Executors** — `/admin/script-executors`

- View: `resources/views/admin/script-executors/index.blade.php` → `layoutnextvite`
- Entries: `admin/loaderAdmin.js` (in content) → `admin/script-executors/index.js` (mounts `#script-executors`)
- Mix: no longer builds this page entry

**Admin Tenant Queues** — `/admin/tenant-queues`

- View: `resources/views/admin/tenant-queues/index.blade.php` → `layoutnextvite`
- Entries: `admin/loaderAdmin.js` → `admin/tenant-queues/index.js` (Vue Router + `#tenant-queues-dashboard`)
- Access: not a `can:*` ability — `TenantQueueController::checkPermissions()` requires `is_administrator`, `config('app.multitenancy')`, and `!config('queue.disable_tenant_tracking')`
- Mix: no longer builds this page entry

**Admin Queues (Horizon)** — queue management iframe

- View: `resources/views/admin/queues/index.blade.php` → `layoutnextvite`
- Only loads `admin/users/loaderUsers.js` for chrome; body is `<iframe src="/admin/horizon">`
- May redirect to tenant-queues when tenant tracking is restricted

**Admin DevLink** — `/admin/devlink/{router?}`

- View: `resources/views/admin/devlink/index.blade.php` → `layoutnextvite`
- Entries: `admin/loaderAdmin.js` → `admin/devlink/index.js` (Vue Router base `/admin/devlink`, mounts `#devlink` / `<dev-link>`)
- Web routes (middleware `admin`): `devlink.index` → `DevLinkController@index` (returns the Blade, or OAuth redirects); `devlink.oauth-client` → `getOauthClient` (**no Blade** — creates Passport client and redirects with query params)
- Mix: no longer builds `admin/devlink/index.js`

**Admin Cases Retention** — `/admin/cases-retention`

- View: `resources/views/admin/cases-retention/index.blade.php` → `layoutnextvite`
- Entries: `admin/loaderAdmin.js` → `admin/cases-retention/index.js` (mounts `#casesRetentionIndex` with `CasesRetentionLogs`; uses `window.Vue`)
- Mix: no longer builds `admin/cases-retention/index.js`

**Admin Logs** — `/admin/logs/{any?}`

- View: `resources/views/admin/logs/index.blade.php` → `layoutnextvite`
- Boot **before** loader: `window.temporal.packages` / `window.packages` (router guards call `hasEmailPackage()` / `hasAiPackage()` via `ProcessMaker.packages`)
- Entries: `admin/loaderAdmin.js` → `admin/logs/index.js` (Vue Router base `/admin/logs`, mounts `#admin-logs-main`)
- **Pitfall:** empty `packages` makes `/` redirect to `/email/errors` and email guard bounce back to `/` → `RangeError: Maximum call stack size exceeded`. Always boot packages before the page entry; guards must not fall back to `/` when no package is present.
- Mix: no longer builds `admin/logs/index.js`

**Environment Variables** — `/designer/environment-variables`, edit

- Views: `processes/environment-variables/index.blade.php`, `edit.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages`; edit also sets `window.temporal.EnvironmentVariableEdit` (ESM-safe form data)
- Entries: `loaderEnvironment.js` → `index.js` / `edit.js`
- Mix: no longer builds `processes/environment-variables/index.js` or `edit.js`

**Screens (Designer)** — `/designer/screens`, configure screen

- Index view: `resources/views/processes/screens/index.blade.php` → `layoutnextvite`
- Boot: `window.temporal.packages` / `window.packages` before `loaderScreens.js`
- Tab apps via child `@append` (same pattern as Processes Designer):
  - `processes/screens/list.blade.php` → `screens/index.js`
  - `processes/screens/myTemplates.blade.php` → `screen-templates/myTemplates.js`
  - `processes/screens/publicTemplates.blade.php` → `screen-templates/publicTemplates.js`
  - Categories tab reuses `categories/list` → `processes/categories/index.js`
- Edit view: `processes/screens/edit.blade.php` → `layoutnextvite`
- Edit boot: packages + `window.temporal.screen` / `assignedProjects` / `isDraft` → `loaderScreens.js` → `screens/edit.js`
- Still Mix: `preview.js` (`screens/preview.blade.php`, `completedScreen.blade.php`); templates import if still on Mix layouts
- Mix: no longer builds `screens/index.js`, `edit.js`, or screen-templates `myTemplates` / `publicTemplates`

**Tasks**

- View: `resources/views/tasks/index.blade.php` → `layoutnextvite`
- Entries: `resources/js/vite/tasks/loaderTasks.js` → `$manager->getScripts()` → `tasks.js`
- Boot: `window.temporal` before loader

**Mobile task edit** — `/tasks/{task}/edit` on mobile

- View: `resources/views/tasks/editMobile.blade.php` → `mobilenextvite`
- Entries: `tasks/loaderTasks.js` (`setupMain` + packages) → deferred `$manager->getScripts()` → `tasks/show.js`
- The inline root mounts `#taskMobile` on `window` `load`, after the Vite modules and ScreenBuilder package scripts have executed
- Register the `screen-renderer-init` listener inside the same `load` callback; `layoutnextvite` yields page scripts before package scripts, so `ProcessMaker.EventBus` is not guaranteed during the first inline tick
- Keep `mixins: addons` for package-provided mobile task behavior

**Task Preview (iframe)** — `/tasks/{task}/edit/preview`

- Route: `tasks.preview` → `TaskController@edit($task, $preview = 'preview')`
- View: `resources/views/tasks/preview.blade.php` — **standalone HTML** (no `@extends`); designed to be embedded as an `<iframe>` in the Tasks inbox sidebar
- Boot (before loader): `window.packages` + `const task` + `const screenBuilderScripts` + `const screenFields` — all as regular `<script>` so they are synchronously available to ESM modules
- Entries: `tasks/loaderPreview.js` (`setupMain` + `screenBuilder`) → `tasks/preview.js`
- EventBus registration: use `window.addEventListener('app-bootstrapped', …)` **not** `'load'`. `setupMain` dispatches `app-bootstrapped` synchronously during `loaderPreview.js`; by `'load'` the screen has already emitted `screen-renderer-init`
- Cross-frame events: `preview.js` dispatches `dataUpdated`, `taskReady`, `userHasInteracted` to `window.parent`. **Guard every `sendEvent` call with `if (!window.frameElement) return`** — without this, `window.parent === window` (direct access, not iframe), events loop back into the same page and trigger an infinite Vue reactivity cycle
- Vue 2 watcher pitfall: `screenFilteredData` computed always returns a **new object reference**. Watching it with `deep: true` and accessing `this.screenFilteredData` inside the handler causes Vue to re-queue the watcher infinitely. **Always use the `newValue` parameter** (`handler(newValue) { sendEvent("dataUpdated", newValue); }`)
- Breadcrumbs guard: `window.ProcessMaker.breadcrumbs` is only set up by the full sidebar/navbar layout; preview runs standalone, so guard: `if (window.ProcessMaker.breadcrumbs) { ... }` in the `task` watcher

**Templates** — import & configure

Shared loader: `templates/loaderTemplates.js` — `setupMain()` + `window.ProcessMaker.packages = window.temporal?.packages || []`. No explicit boot script needed for packages.

*Template Import* — `/template/{type}/import` (`templates.import`)

- View: `resources/views/templates/import.blade.php` → `layoutnextvite`
- Asset type via `<meta name="import-template-asset-type" content="{{ $type }}">` (read in JS)
- Entry: `templates/import/index.js` — Vue Router on `window.ProcessMaker.Router`; reuses `ImportManagerView` from `processes/import`, `State` from `processes/export`, adds `TemplateDetailConfigs`

*Template Configure* — `/template/{type}/{template}/configure` (`templates.configure`)

- View: `resources/views/templates/configure.blade.php` → `layoutnextvite`
- Boot (in `@section('js')` before loader): `window.temporal.templateConfigurations = { data, templateType, screenTypes }`
- Entry: `templates/configure.js` — registers `ProcessTemplateConfigurations` / `ScreenTemplateConfigurations`, mounts `#configureTemplate` with `mixins: addons` and reads all data from `window.temporal.templateConfigurations`
- **Pitfall**: `:permission="{{ ... }}"` passes PHP `true` as string `"1"`; should use `:permission="@json(...)"`. Flag for future fix.

*Template Assets* — `templates.assets`

- View: `resources/views/templates/assets.blade.php` → `layoutnextvite`
- No boot script; data is read from `localStorage.getItem("templateAssetsState")` in `mounted()` (state is written before the browser navigates to this page)
- Entry: `templates/assets.js` — mounts `#template-asset-manager` with `TemplateAssetsView`

Still Mix: `templates/export-screen`, `templates/import-screen`, `templates/list` (child partial — no standalone layout)

**Notifications** — `/notifications`

- Route: `notifications.index` → `NotificationController@index`
- View: `resources/views/notifications/index.blade.php` → `layoutnextvite`
- No explicit boot script needed; `loaderNotifications.js` reads `window.temporal?.packages` with optional chaining (defaults to `[]`)
- Entries: `notifications/loaderNotifications.js` (`setupMain` + packages) → `notifications/index.js` (mounts `#notifications` with `NotificationsList`, `filter`, `filterComments`)
- Mix: no longer builds notifications page entry

**Processes Catalogue (desktop)**

- Route: `/process-browser` (`process.browser.index`)
- View: `resources/views/processes-catalogue/index.blade.php` → `layoutnextvite`
- Entries: `resources/js/processes-catalogue/loaderProcessesCatalogue.js` → `$manager->getScripts()` → `processesCatalogue.js`
- Mobile still Mix

**Cases**

- Route: `/cases`
- View: `resources/views/cases/casesMain.blade.php` → `layoutnextvite`
- Entries: `resources/jscomposition/cases/casesMain/loaderCasesMain.js` → GlobalScripts + `$manager->getScripts()` → `casesMain.js`
- App components/routes/store stay in the same `jscomposition/cases/casesMain/` folder
- Boot: `window.temporal.user` / `packages`; loader copies onto `window.ProcessMaker` (variables read `window.ProcessMaker.user`)

**Case Detail**

- View: `resources/views/cases/edit.blade.php` → `layoutnextvite`
- Entries: `resources/jscomposition/cases/casesDetail/loaderCasesDetail.js` → `@vite` `resources/js/initialLoad.js` + GlobalScripts + modeler package scripts (+ optional package-files) → `casesDetail.js`
- Boot: `window.temporal` holds request/modeler payload; loader applies `ProcessMaker.modeler` / EventBus / `PMBlockList`
- Variables read from `window.temporal` (ESM-safe)
- Modeler SVG icons: Vite plugin rewrites `@processmaker/modeler` asset URLs to `/js/img/` (same files Mix copies from `node_modules/@processmaker/modeler/dist/img`)

**Modeler** — `/modeler/{process}`

- View: `resources/views/processes/modeler/index.blade.php` → `layoutnextvite`
- Boot: `window.temporal` (breadcrumbData, modeler payload, warnings, packages) before loader — `setupMain()` replaces `window.ProcessMaker`
- Entries: `modeler/loaderModeler.js` (`setupMain` + monaco + nodeTypes + ScreenBuilder/VueFormElements + dynamic `import('./initialLoad.js')`) → skip Mix `initialLoad.js` from `$manager->getScriptWithParams()` → remaining package Mix scripts (`defer`) → `leave-warning.js` → `modeler/index.js` mounts `#modeler-app` on `window` `load`
- Mix: no longer builds `processes/modeler/index.js`. Keep Mix `initialLoad.js` and `process-map.js` for inflight pages; `requests/show` loads modeler `initialLoad.js` through its Vite loader
- Pitfall: `initialLoad.js` uses `window.Vue` / `window.ProcessMaker`; must run after `setupMain()`. Inspector extensions listen on `modeler-init` before the Vue app mounts

**Request detail** — `/requests/{request}`

- View: `resources/views/requests/show.blade.php` → `layoutnextvite`
- Entry: `resources/js/requests/loaderRequestsShow.js` initializes `setupMain`, Monaco, modeler, VueFormElements, ScreenBuilder, request components, and the modeler `initialLoad.js`
- Package scripts from `$manager->getScriptWithParams()` and `$manager->getScripts()` remain classic deferred scripts because packages still publish Mix assets
- The Blade Vue root remains inline and mounts `#request` on `window` `load`, after Vite and package scripts are available
- Inflight modeler data and its `modeler-start` listener are also initialized on `window` `load`; this prevents access to `ProcessMaker.EventBus` before the Vite loader has booted it
- The optional `package-files` manager remains a vendor Mix asset and is intentionally not part of the application Vite entry

**Mobile request detail** — `/requests/{request}` on mobile

- View: `resources/views/requests/showMobile.blade.php` → `mobilenextvite`
- Reuses `requests/loaderRequestsShow.js` and `requests/show.js` so mobile and desktop share the same component registrations
- Loads the modeler `initialLoad.js` entry and keeps the optional `package-files` manager as a deferred vendor Mix script
- The `#requestMobile` Vue root mounts on `window` `load`, allowing Vite modules and package addons to finish before `mixins: addons` is evaluated
- Keep `layouts/mobile.blade.php` for routes that have not migrated; `mobilenextvite` is the Vite wrapper for mobile pages

**Login / auth forms**

- Login: `auth.newLogin` — `@vite` login entry + translations
- Password reset / email: `auth.layouts.auth` — same Vite scripts pattern (not `auth-language-scripts` Mix bundle)

**Still Mix**

- Processes Catalogue mobile: `resources/views/processes-catalogue/mobile.blade.php`
- Shared Designer child lists / other Designer routes still Mix (`templates/list` on some paths, script preview, modeler **inflight** / `process-map.js`, screens **preview**, …) even when listing tabs under Vite `/designer/screens`, `/designer/scripts`, or `/processes`
- Password change, and most other non-listed admin/designer pages
- `requests/show.blade.php` is Vite-backed through `requests/loaderRequestsShow.js`; the redundant `collapseDetails.css` Mix link was removed because `layoutnextvite` already loads it with `@vite`. The optional `package-files` vendor manager remains Mix-backed.
