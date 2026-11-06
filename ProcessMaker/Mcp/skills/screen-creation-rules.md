# Skill: Always build ProcessMaker screens with Vue Component Editor

## Mandatory rule

Whenever you create or update a ProcessMaker screen, you MUST:

1. Render the main UI through Screen Builder’s **Vue component Editor** control (`component: "VueComponentRenderTemplate"`).
2. Keep `contentVue` as a **thin mount** of one (or a few) named Vue component tags — for example `<PetRegistrationForm v-model="data" :submit="submit" />`.
3. Put all layout, fields, copy, and interactivity **inside that Vue component** (catalog `@/…vue` or DB-only `custom://slug`). Do **not** dump form markup, pages of HTML, or BootstrapVue field trees into `contentVue`.
4. Do **not** build the main UI with native Screen Builder fields (`FormInput`, `FormSelect`, `FormHtmlViewer`, etc.) as the primary layout. Those may be used only as thin helpers (e.g. a submit `FormButton`) when the Vue component does not own that action.
5. **Register every new official (`@/…`) Vue component** before (or immediately after) creating the screen that mounts it — dropping a `.vue` file on disk is not enough. See **Register official package-plg components** below.

## How the control works (runtime)

Inspector label: **Vue component Editor** (group: **TCE Controls**).
Runtime component: **`VueComponentRenderTemplate`**.

At render time the control:

- Compiles `contentVue` as a Vue **2** template string (should be a short mount wrapper).
- Evaluates `handlerVue` as a trusted function body that must `return { ... }` and merges that object as a **mixin**.
- Injects screen request `$data` as `data` on that inner instance (same object as Screen Builder transient data).
- Resolves tags used in `contentVue` from the explicit `config.components` list (`{ "name", "key" }`), then falls back to globally registered components.
- Exposes `submit()` so the mounted component / mixin can trigger form submit.

Do **not** parse `contentVue` to invent dependencies. Every catalog or custom tag in `contentVue` **must** appear in `config.components` with a real `{ "name", "key" }`.

## Required pattern: mount a Vue component

`contentVue` is a wiring layer only. The real UI lives in a registered Vue component.

### Good (required)

```html
<PetRegistrationForm
  v-model="data"
  :submit="submit"
  :configuration="configuration"
/>
```

### Bad (forbidden)

```html
<div class="form-root">
  <h1>Alta de mascotas</h1>
  <input v-model="data.name" />
  <select v-model="data.species">...</select>
  <!-- large inline layout / fields / styles embedded in contentVue -->
</div>
```

If you catch yourself writing more than a short component tag (plus optional trivial wrapper) in `contentVue`, stop and move that markup into a Vue component instead.

## Where the Vue component comes from

Keys look like:

| Kind | Key shape | When to use |
|------|-----------|-------------|
| Catalog / package | `@/…/ComponentName.vue` | Official PLG / package-plg components already registered for Screen Builder |
| DB-only custom | `custom://slug` | Admin Custom Vue Components with no official fallback |

Rules:

1. Prefer **reusing** an existing component. Discover with `list-screens` / `get-screen` and copy its `contentVue`, `handlerVue`, and especially each `{ "name", "key" }` from `config.components`.
2. Never invent or guess a `key`. Copy it from a working reference screen or from the Custom Vue Components / package catalog.
3. If the UI does not exist yet, **create the Vue component first** (package catalog SFC + **catalog registration** + build/publish, or a `custom://` Custom Vue Component), then publish the screen that mounts it. Do not ship the UI as inline `contentVue` while waiting for a component.
4. Screens MCP creates/updates **screens** only. Component source lives outside screen JSON (`template` / `script` / `.vue` files). Do not pretend a giant `contentVue` string is a component.

`componentName` is documentation / inspector only; runtime resolution uses the tag in `contentVue` plus `components` / globals.

## Register official package-plg components (mandatory for new `@/…` SFCs)

Screen Builder resolves official tags via `registerPublicScreenCatalog()`, which loads **only** entries listed in:

`package-plg/resources/js/designer/public-screen-components.json`

A Vite glob of `examples/**/*.vue` bundles the SFC, but **without a catalog row the preview stays blank** (siblings already in the JSON, e.g. `PaymentStepForm`, still render).

When you add a new official component (typical path: `resources/js/components/screen-builder/examples/YourForm.vue`):

1. **Catalog** — append `{ "name": "YourForm", "key": "@/components/screen-builder/examples/YourForm.vue" }` to `public-screen-components.json`. If the parent uses child components, list them in the parent's nested `components` array **and** as their own top-level `{ name, key }` rows.
2. **Build** — from `Sites/packages/package-plg`: `npm run prod` (rebuilds form + display screen-builder bundles).
3. **Publish** — from `Sites/processmaker`: `php artisan vendor:publish --tag=package-plg --force` (PM serves `public/vendor/processmaker/packages/package-plg/js/`, not the package repo `public/` tree).
4. **Hard-refresh** Screen Builder in the browser after publish.

Do this **before claiming the screen is done**. Skipping catalog/build/publish is a incomplete delivery even if `create-screen` succeeded.

`custom://` components are registered in Admin → Custom Vue Components (DB); they do **not** use `public-screen-components.json`, but still need a real `{name,key}` on the screen.

## Validate before save (mandatory)

Call **`validate-screen`** with the draft `config` (and `type`) before `create-screen` / `update-screen`. create/update also run the same checks and **reject** hard errors.

| Code | Severity | Meaning |
|------|----------|---------|
| `select_options_empty` | error | `FormSelectList` + `provideData` has no options |
| `select_options_list_missing` | error | Has `jsonData` but missing/empty `optionsList` — dropdown renders **empty** and required selects block submit |
| `vue_content_missing` / `vue_components_missing` / `vue_component_entry_invalid` | error | `VueComponentRenderTemplate` wiring incomplete |
| `prefer_vue_component_editor` | warning | FORM built only with native SB controls |
| `vue_handler_missing_return` / `vue_ignore_mustache` | warning | Vue editor hygiene |

### FormSelectList (if you must use native selects)

`jsonData` alone is **not enough**. Screen Builder needs a non-empty `optionsList`:

```json
"options": {
  "dataSource": "provideData",
  "key": "value",
  "value": "content",
  "jsonData": "[{\"value\":\"Travel\",\"content\":\"Travel\"}]",
  "optionsList": [
    { "value": "Travel", "content": "Travel" }
  ]
}
```

Prefer putting selects inside a registered Vue component (see PTO / Expense Registration examples) instead of native `FormSelectList`.

## Workflow (follow in order)

1. **Discover** — `list-screens` / `get-screen` so you do not duplicate an existing screen. Prefer copying a similar screen’s Vue Component Editor block when reusing a catalog/custom tag.
2. **Ensure the Vue component exists and is registered** — reuse a real `{name,key}` already in the catalog, or create the SFC + add `public-screen-components.json` + `npm run prod` + `vendor:publish --tag=package-plg --force` (or create/register a `custom://` component) **before** the screen depends on it.
3. **Validate** — `validate-screen` with `config` + `type`; fix every `errors[]` entry.
4. **Create or update** — `create-screen` / `update-screen` with `type` `FORM` or `DISPLAY`, title, description, `screen_category_id`, and a page whose `items` contain a `VueComponentRenderTemplate` control whose `contentVue` only mounts that component.
5. **Verify** — `get-screen` and confirm:
   - `component` is `VueComponentRenderTemplate`
   - `ignoreMustache` is true
   - `contentVue` is a thin mount of a named tag (not a full page of markup)
   - `handlerVue` starts with `return`
   - every non-global tag in `contentVue` has a matching `{name,key}` in `components`
   - for new `@/…` keys: the name appears in `public-screen-components.json` and the published vendor JS includes the component (Screen Builder preview actually renders)

## Required control shape

```json
{
  "component": "VueComponentRenderTemplate",
  "editor-component": "VueComponentEditor",
  "editor-control": "VueComponentEditor",
  "label": "Vue component Editor",
  "inspector": [],
  "config": {
    "icon": "fas fa-pencil-ruler",
    "interactive": true,
    "componentName": "PetRegistrationForm",
    "contentVue": "<PetRegistrationForm v-model=\"data\" :submit=\"submit\" :configuration=\"configuration\" />",
    "handlerVue": "return {\n  data() {\n    return {\n      data: this.data || {},\n      configuration: (defaultConfiguration) => ({ ...defaultConfiguration }),\n    };\n  },\n};",
    "components": [
      {
        "name": "PetRegistrationForm",
        "key": "@/screens/pets/PetRegistrationForm.vue"
      }
    ],
    "renderVarHtml": null,
    "ignoreMustache": true
  }
}
```

The `key` above is an **example shape**. Replace it only with a key taken from `get-screen` on a working reference or from the real component catalog / Custom Vue Components admin. Never invent paths.

### Typical page config

```json
{
  "name": "Page 1",
  "items": [
    {
      "component": "VueComponentRenderTemplate",
      "editor-component": "VueComponentEditor",
      "editor-control": "VueComponentEditor",
      "label": "Vue component Editor",
      "inspector": [],
      "config": {
        "componentName": "PetRegistrationForm",
        "contentVue": "<PetRegistrationForm v-model=\"data\" :submit=\"submit\" />",
        "handlerVue": "return {\n  created() {\n    if (this.data && this.data.petName === undefined) {\n      this.$set(this.data, 'petName', '');\n    }\n  },\n};",
        "components": [
          {
            "name": "PetRegistrationForm",
            "key": "@/screens/pets/PetRegistrationForm.vue"
          }
        ],
        "ignoreMustache": true,
        "interactive": true,
        "icon": "fas fa-pencil-ruler"
      }
    }
  ]
}
```

Add a `FormButton` with `event: "submit"` only when the FORM needs an external submit control. Prefer calling `this.submit()` / `:submit="submit"` from the Vue component when the UI owns the action.

Useful full-height CSS many screens share:

```css
[data-cy="screen-renderer"] { height: 100%; }
[data-cy="screen-renderer"] > div,
[data-cy="screen-renderer"] > div > div,
[data-cy="screen-renderer"] > div > div > div { height: 100%; }
```

## Field meanings

| Field | Purpose |
|-------|---------|
| `componentName` | Inspector / docs label for the primary mounted component. |
| `contentVue` | Thin Vue 2 **template** that mounts registered component tag(s). Prefer one root component tag. |
| `handlerVue` | Trusted mixin body. Must `return { ... }`. Init reactive `$data` keys with `created()` + `this.$set(this.data, …)`. Keep thin — no UI markup here. |
| `components` | Explicit `{ "name", "key" }` list for every catalog (`@/…vue`) or custom (`custom://slug`) tag used in `contentVue`. |
| `ignoreMustache` | Keep `true` for Vue templates. |
| `interactive` | Keep `true`. |

`data` in `contentVue` / `handlerVue` is Screen Builder request `$data`. Bind into the child with `v-model` / props; write back with emits or `$set`. Request user (when needed) is `this.data._user` or `ProcessMaker.user`, not `this._user`.

## Authoring rules

- Vue 2 Options API only inside `handlerVue` and custom component scripts (no `<script setup>`, no TypeScript in runtime DB components).
- Keep `handlerVue` thin: init `$data`, small helpers, submit bridge. Heavy markup stays in the mounted Vue component.
- Props / bindings for external data; never hardcode secrets, tokens, or tenant-private credentials in `contentVue`, `handlerVue`, or component source.
- Do not invent `components[].key` values. Copy from a reference screen or the real catalog / Custom Vue Components list.

## Hard don’ts

- Do not put the main UI (forms, layouts, result lists, brand chrome) inline in `contentVue`.
- Do not leave `components` empty when `contentVue` uses a custom/catalog tag.
- Do not ship a screen whose main UI is only native SB controls or HTML viewers.
- Do not omit `VueComponentRenderTemplate` when publishing the screen config.
- Do not reference a catalog/custom tag in `contentVue` without a matching `{name,key}` copied from a real source.
- Do not add a new `@/…` SFC under `examples/` (or elsewhere) without also updating `public-screen-components.json`, rebuilding, and publishing assets.
- Do not put secrets in component config.

## Done checklist

- [ ] Screen exists and id is known
- [ ] Page items use `VueComponentRenderTemplate`
- [ ] `contentVue` only mounts named Vue component tag(s)
- [ ] UI implementation lives in the mounted component (not inline in `contentVue`)
- [ ] `handlerVue` returns an object and stays thin
- [ ] `components` has real copied `{name,key}` entries for every mounted tag
- [ ] New official `@/…` components are in `public-screen-components.json`
- [ ] `npm run prod` (package-plg) + `vendor:publish --tag=package-plg --force` (core) ran after catalog/SFC changes
- [ ] Screen Builder preview actually renders the mounted component
- [ ] `get-screen` shows the control wired as intended

## Screen Builder config safety (required)

Every control in `page.items` **must** include `"inspector": []` (or a real inspector array). Omitting `inspector` crashes Screen Builder (`item.inspector.forEach` → undefined).

Pages should include `"items": []` even when empty. Prefer `"computed": []` and `"watchers": []` over `null`.

Example control shape:

```json
{
  "component": "VueComponentRenderTemplate",
  "editor-component": "VueComponentEditor",
  "editor-control": "VueComponentEditor",
  "label": "Vue component Editor",
  "inspector": [],
  "config": { }
}
```

The MCP server also backfills missing `inspector` / `items` on create and update, but always send them explicitly.
