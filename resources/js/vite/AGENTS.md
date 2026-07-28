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
| `layouts.layoutnextvite` | `@vite` of `app.scss`, `sidebar`, `collapseDetails`, `tailwind.css` | Authenticated Vite pages (Tasks, Processes Catalogue desktop, Cases) |
| Standalone / `auth.layouts.auth` | Page-level `@vite` | Login and password-reset style pages |

File: `resources/views/layouts/layoutnextvite.blade.php` (must be `.blade.php`).

## How routes switch to Vite (current pattern)

There is **no** `VITE_VIEW` env map. Migration is done **in place** on the existing Blade:

1. Change `@extends(...)` to `layouts.layoutnextvite` (in-app) or keep standalone/auth layout.
2. Replace `mix('js/...')` page bootstraps with `@vite([...])`.
3. Register the JS/CSS entry in `vite.config.js` → `laravel({ input: [...] })`.
4. Keep Mix-built package scripts as classic `<script src="...">` when packages still ship Mix assets (`$manager->getScripts()`, `GlobalScripts`, etc.).

Entries may live under:

- `resources/js/vite/<area>/` (Tasks, auth)
- Feature folder next to the UI (e.g. `resources/js/processes-catalogue/`)
- Composition tree (e.g. `resources/jscomposition/cases/casesMain/`)

Prefer co-locating the Vite entry with the feature source when that tree already owns the page.

## Folder layout

```
resources/views/layouts/layoutnextvite.blade.php
resources/views/tasks/index.blade.php                 ← Vite
resources/views/processes-catalogue/index.blade.php   ← Vite (desktop)
resources/views/cases/casesMain.blade.php             ← Vite
resources/views/auth/newLogin.blade.php               ← Vite
resources/views/auth/layouts/auth.blade.php           ← Vite scripts (reset/email)
resources/js/vite/tasks/                              ← Tasks entries
resources/js/vite/auth/login.js                       ← Login / auth layout entry
resources/js/processes-catalogue/loaderProcessesCatalogue.js
resources/js/processes-catalogue/processesCatalogue.js
resources/jscomposition/cases/casesMain/loaderCasesMain.js
resources/jscomposition/cases/casesMain/casesMain.js
resources/js/translations/index.js                    ← shared i18n Vite entry
resources/sass/*.scss|css                             ← also compiled by Vite for layoutnextvite
vite.config.js
```

## Current migrations

| Route area | Status | View | JS entry(ies) |
|------------|--------|------|----------------|
| Tasks inbox | **Vite** | `tasks.index` + `layoutnextvite` | `vite/tasks/loaderTasks.js` → ScreenBuilder scripts → `vite/tasks/tasks.js` |
| Processes Catalogue (desktop) | **Vite** | `process.browser.index` (`/process-browser`) + `layoutnextvite` | `processes-catalogue/loaderProcessesCatalogue.js` → ScreenBuilder scripts → `processesCatalogue.js` |
| Cases | **Vite** | `cases.casesMain` (`/cases`) + `layoutnextvite` | `jscomposition/.../loaderCasesMain.js` → GlobalScripts / ScreenBuilder → `casesMain.js` |
| Login | **Vite** | `LOGIN_VIEW` / `auth.newLogin` | Head: `app.scss` + `vite/auth/login.js`; footer: GlobalScripts (skip dynamic-ui) + `translations/index.js` |
| Auth layout (reset / email) | **Vite scripts** | `auth.layouts.auth` | `vite/auth/login.js` → GlobalScripts (skip dynamic-ui) → packages boot → `translations/index.js` |
| Processes Catalogue (mobile) | Mix | `processes-catalogue/mobile.blade.php` | Mix catalogue / mobile bundle |

## Registered Vite inputs (`vite.config.js`)

```
resources/js/vite/auth/login.js
resources/js/translations/index.js
resources/js/vite/tasks/loaderTasks.js
resources/js/vite/tasks/tasks.js
resources/js/processes-catalogue/loaderProcessesCatalogue.js
resources/js/processes-catalogue/processesCatalogue.js
resources/jscomposition/cases/casesMain/loaderCasesMain.js
resources/jscomposition/cases/casesMain/casesMain.js
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
   - **Script order:**
     1. Inline boot (`window.temporal = {...}`; set `window.packages` if `setupMain` needs it)
     2. `@vite` loader (`setupMain` + ScreenBuilder as needed)
     3. Package scripts (`defer`)
     4. `@vite` page app

2. **JS entry**
   - Vue 2 Options API; `import Vue from 'vue'` / `import VueRouter from 'vue-router'` in ESM.
   - Register in `vite.config.js` → `laravel({ input: [...] })`.
   - `.vue` extension optional (`resolve.extensions` includes `.vue`).
   - Mirror `window.ProcessMaker` **and** `window.Processmaker` when legacy mixins expect the lowercase `m`.
   - Prefer reading Blade boot data from `window.temporal` / `window.ProcessMaker` (ESM cannot see Blade `const` / `let`).
   - Avoid circular ESM: do not import feature `EventBus` from a Mix page entry that also imports the feature graph.

3. **Controller**
   - Usually no change if the Blade name stays the same.
   - Pass the same view data the page already expected.

4. **Verify**
   - With `vite:dev`: Network shows `127.0.0.1:5173` for `@vite` assets; Mix URLs stay on the app host.
   - Without Vite running: need a prior `vite:build` (manifest).
   - `mix('...')` must not point at `:5173` (delete stale `public/hot` if it does).
   - **PHPUnit:** `Tests\TestCase` calls `withoutVite()` so `@vite` Blades do not need `public/build/manifest.json`. Feature tests only see server HTML (JS-mounted DOM is not executed). Mix helpers still need `mix-manifest.json` if the Blade still calls `mix()`.

5. **Do not**
   - Alias `@vite` in `vite.config.js` (shadows `@vite/client` / `@vite/env`).
   - Point Vite `hotFile` at `public/hot`.
   - Include `auth.partials.auth-language-scripts` on Vite auth pages (still references dead Mix `builds/login/js/...` → `MixFileNotFoundException`).

## Known Vite config notes (`vite.config.js`)

- **`resolve.extensions`**: includes `.vue`. If both `Foo.js` and `Foo.vue` exist, `.js` wins.
- **Styles alias**: `styles` / `~styles` → `resources/sass`; Sass importer mirrors Webpack `~styles/...`.
- **Tailwind**: entry `resources/sass/tailwind.css` + root `postcss.config.js`.
- **Fonts**: Sass `$FontPathOpenSans` / `$fa-font-path` are root-relative (`/css/precompiled/...`). During `vite:dev`, `server.proxy['/css']` forwards to `APP_URL` (avoids CORS).
- **Vue2 plugin**: `includeAbsolute: false` so `/img/...` stays Laravel public URLs.
- **`refresh`**: `resources/views/vite/**` (optional), `resources/js/**/*.{js,vue}`, `resources/sass/**`.
- **YAML plugin**: `@rollup/plugin-yaml`.

## Quick reference

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

**Login / auth forms**

- Login: `auth.newLogin` — `@vite` login entry + translations
- Password reset / email: `auth.layouts.auth` — same Vite scripts pattern (not `auth-language-scripts` Mix bundle)

**Still Mix**

- Processes Catalogue mobile: `resources/views/processes-catalogue/mobile.blade.php`
