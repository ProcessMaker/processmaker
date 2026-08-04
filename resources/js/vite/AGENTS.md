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

Shared admin chrome: `resources/js/admin/loaderAdmin.js` (`setupMain` + packages) is used by Script Executors, Tenant Queues, DevLink, and Cases Retention.

## Folder layout

```
resources/views/layouts/layoutnextvite.blade.php
resources/views/tasks/index.blade.php                 ← Vite
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
resources/js/vite/tasks/                              ← Tasks entries
resources/js/vite/auth/login.js                       ← Login / auth layout entry
resources/js/admin/loaderAdmin.js                     ← shared admin setupMain loader
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
| Processes (Designer) | **Vite** | `processes.index` + `layoutnextvite`; apps via child `@append` | `processes/loaderProcesses.js` → `processes.js` / `templates` / `categories` / `archived` |
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
resources/js/processes/loaderProcesses.js
resources/js/processes/processes.js
resources/js/processes/edit.js
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
   - Leave Mix entries that other routes / shared child Blades still load (screens, scripts, categories on non-Vite pages, etc.).

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
- Mix: `webpack.mix.js` no longer builds `resources/js/processes/index.js` / `edit.js`. Other Designer Mix bundles (screens, scripts, modeler, …) remain.

**Process Configure** — `/processes/{process}/edit`

- View: `resources/views/processes/edit.blade.php` → `layoutnextvite`
- Boot: set `window.temporal.packages` / `window.packages` before loader
- Entries: `loaderProcesses.js` → `edit.js` (registers `CategorySelect`, `ProcessesPermissions`)
- Vue root stays **inline** in the Blade (`window.addEventListener('load', …)`) so Blade `@json(...)` boot data and plugin `mixins: addons` keep working without a full ESM rewrite
- Plugin addons still come from `layoutnextvite` (`var addons = []` + `script` / `script_mix`)

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

**Tasks**

- View: `resources/views/tasks/index.blade.php` → `layoutnextvite`
- Entries: `resources/js/vite/tasks/loaderTasks.js` → `$manager->getScripts()` → `tasks.js`
- Boot: `window.temporal` before loader

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

**Login / auth forms**

- Login: `auth.newLogin` — `@vite` login entry + translations
- Password reset / email: `auth.layouts.auth` — same Vite scripts pattern (not `auth-language-scripts` Mix bundle)

**Still Mix**

- Processes Catalogue mobile: `resources/views/processes-catalogue/mobile.blade.php`
- Shared Designer child lists / other Designer routes still Mix (`templates/list`, `categories/list`, `archivedList`, screens, scripts, modeler, …) even when embedded under Vite `/processes`
- Admin profile, logs, password change, and most other non-listed admin/designer pages
