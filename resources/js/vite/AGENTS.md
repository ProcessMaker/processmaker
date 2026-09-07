# Vite views (ProcessMaker)

Guide for humans and coding agents working under `resources/js/vite`, Vite Blade layouts, and the parallel Vite asset pipeline.

Vite runs **alongside** Laravel Mix. Do not replace Mix globally. Migrate routes one at a time.

## Mental model

| Pipeline | Owns | Blade helper | Dev server / output |
|----------|------|--------------|---------------------|
| **Mix** | Most of the app (`public/js`, `public/css`) | `mix('js/...')`, `mix('css/...')` | `npm run development` / `watch` → `public/` + `mix-manifest.json` |
| **Vite** | Entries under `resources/js/vite`, shared Sass entries, Vite-backed pages | `@vite([...])` | `npm run vite:dev` → `:5173` + `storage/vite.hot`; `npm run vite:build` → `public/build` + manifest |

- Mix HMR file (if used): `public/hot` — **reserved for Mix**. Never point Vite hot here.
- Vite hot file: `storage/vite.hot` (configured in `vite.config.js` and `Vite::useHotFile()` in `ProcessMakerServiceProvider`).
- If Vite wrote `public/hot`, `mix('js/...')` would rewrite Mix URLs to `http://127.0.0.1:5173/...` and 404 (e.g. `typeForm.js`).

## Layouts

| Layout | CSS | Use when |
|--------|-----|----------|
| `layouts.layoutnext` | `mix('css/app.css')` etc. | Legacy Mix pages |
| `layouts.layoutnextvite` | `@vite` of `app.scss`, `sidebar`, `collapseDetails`, `tailwind.css` | Authenticated Vite-backed pages (e.g. tasks) |
| Standalone Blade | `@vite` for JS + needed CSS in the page itself | Demo / auth experiments without app chrome |

File: `resources/views/layouts/layoutnextvite.blade.php` (must be `.blade.php`).

## How routes switch to Vite (current pattern)

There is **no** `VITE_VIEW` env map anymore. Migration is done **in place** on the existing Blade (or a dedicated `resources/views/vite/...` page for demos):

1. Change `@extends(...)` to `layouts.layoutnextvite` (in-app) or keep a standalone layout.
2. Replace `mix('js/...')` page bootstraps with `@vite(['resources/js/vite/...'])`.
3. Register the JS/CSS entry in `vite.config.js` → `laravel({ input: [...] })`.
4. Keep Mix-built package scripts as classic `<script src="...">` when packages still ship Mix assets.

Login still uses the classic path: `LoginController` → `config('app.login_view')` / `LOGIN_VIEW` (default `auth.newLogin`), else `auth.login`. A Vite login **entry** (`resources/js/vite/auth/login.js`) remains registered in `vite.config.js` for a future auth migration; there is currently **no** `resources/views/vite/auth/` Blade.

## Folder layout

```
resources/views/vite/          ← optional Vite-only Blades (demo)
resources/views/layouts/layoutnextvite.blade.php
resources/views/tasks/index.blade.php  ← migrated in place to Vite layout + entries
resources/js/vite/             ← JS entrypoints
resources/sass/app.scss        ← shared styles also compiled by Vite (same sources as Mix)
vite.config.js                 ← inputs, aliases, extensions, server, hotFile
```

Current examples:

| Route area | View | JS entry(ies) |
|------------|------|-----------------|
| Tasks inbox | `tasks.index` → `resources/views/tasks/index.blade.php` + `layoutnextvite` | `loaderTasks.js`, then package scripts, then `tasks.js` |
| Demo | `/vite` → `vite.index` | `resources/js/vite/sample/app.js` |
| Login (not switched) | `LOGIN_VIEW` / `auth.login` | Mix / legacy; Vite entry `auth/login.js` reserved |

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
   - Prefer editing the existing Mix Blade in place (as with tasks), or add `resources/views/vite/<area>/<page>.blade.php` for greenfield/demo pages.
   - Prefer `@extends('layouts.layoutnextvite')` for in-app pages.
   - Load page JS with `@vite(['resources/js/vite/...'])`. Do **not** rely on `mix('css/app.css')` for Vite-only chrome if using `layoutnextvite`.
   - Keep Mix package scripts that are still Mix-built as classic `<script src="{{ mix(...) }}">` / `$manager->getScripts()` when required.
   - **Script order matters** when Mix packages depend on boot data / ScreenBuilder:
     1. Inline boot (`window.temporal = {...}`)
     2. `@vite` loader entry (e.g. `loaderTasks.js`)
     3. Package scripts (`defer`)
     4. `@vite` page app (e.g. `tasks.js`)

2. **JS entry**
   - Add `resources/js/vite/<area>/<entry>.js` (Vue 2 Options API; `import Vue from 'vue'` in ESM).
   - Register the path in `vite.config.js` → `laravel({ input: [...] })`.
   - `.vue` extension in imports is **optional**: `resolve.extensions` includes `.vue` (see below). Explicit `.vue` is still fine and unambiguous.
   - Side-effect modules used only for prototypes must `export default` if imported as default (see `resources/js/modules/isPMQL.js`).
   - Prefer mirroring Mix globals when shared components expect `window.Processmaker` (lowercase `m`) as well as `window.ProcessMaker`.

3. **Controller**
   - Usually **no** controller change if the Blade name stays the same (`tasks.index`).
   - Pass the **same** view data the page already expected. Missing vars cause Blade/Vue errors.
   - For login later: wire a Vite Blade via `LOGIN_VIEW` or restore a map; do not leave a Vite view that expects `$block` / `addons` without providing them.

4. **Verify**
   - With `vite:dev`: Network shows `127.0.0.1:5173` for `@vite` assets; Mix URLs stay on the app host (`/js/...`, `/css/...`).
   - Without Vite running: `@vite` needs a prior `vite:build` (manifest), or the page errors.
   - `mix('...')` must **not** point at `:5173` (if it does, delete stale `public/hot` and confirm `Vite::useHotFile(storage_path('vite.hot'))`).

5. **Do not**
   - Alias `@vite` in `vite.config.js` (shadows `@vite/client` / `@vite/env`).
   - Assume absolute `/img/...` or `/css/...` inside Vite-served CSS resolve to Vite (fonts use `APP_URL` during `serve`).
   - Point Vite `hotFile` at `public/hot`.

## Known Vite config notes (`vite.config.js`)

- **`resolve.extensions`**: includes `.vue` so imports may omit the extension (`import X from './Foo'` resolves `Foo.vue`). If both `Foo.js` and `Foo.vue` exist, `.js` wins (listed first).
- **Styles alias**: `styles` / `~styles` → `resources/sass` (same as Mix) for `@import '~styles/variables'` in SFCs; Sass importer mirrors Webpack `~styles/...`.
- **Tailwind**: entry `resources/sass/tailwind.css` + root `postcss.config.js`.
- **Fonts in `serve`**: Sass `$FontPathOpenSans` / `$fa-font-path` prefixed with `APP_URL`; in `build`, root-absolute `/css/precompiled/...`.
- **Vue2 plugin**: `includeAbsolute: false` so `/img/...` stays Laravel public URLs.
- **`refresh`**: Blades under `resources/views/vite/**`, plus `resources/js/**/*.{js,vue}` and `resources/sass/**` (note: in-place Blades like `tasks/index.blade.php` are outside the vite views glob — hard-refresh or extend `refresh` if full reload on Blade edit is required).
- **YAML plugin**: `@rollup/plugin-yaml` for YAML imports used by shared modules.

## Quick reference: existing migrations

**Tasks (live)**

- View: `resources/views/tasks/index.blade.php` → `layouts.layoutnextvite`
- Entries: `resources/js/vite/tasks/loaderTasks.js` then package scripts then `resources/js/vite/tasks/tasks.js`
- Boot: inline `window.temporal` **before** `@vite` loader (see `@section('js')`)
- Loader copies `temporal.*` onto `window.ProcessMaker` / `window.Processmaker` for ScreenBuilder and legacy mixins

**Demo**

- Route: `GET /vite` → `vite.index` (`routes/web.php`)
- View: `resources/views/vite/index.blade.php`
- Entry: `resources/js/vite/sample/app.js` (+ `DemoBadge.vue`)

**Login (not migrated)**

- Controller: `LoginController@showLoginForm` → `config('app.login_view')` (`LOGIN_VIEW`, default `auth.newLogin`) or `auth.login`
- Reserved Vite entry (not wired to a Blade yet): `resources/js/vite/auth/login.js`
