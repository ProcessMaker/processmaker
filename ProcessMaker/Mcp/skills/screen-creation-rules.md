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

## Domain behavior lives inside the Vue component (mandatory)

**Never** assemble a FORM from native Screen Builder controls (`FileUpload`, `FormSelectList`, `SignaturePad`, `FormInput`, …) as siblings for the main UX. The screen page should mount **one** coherent Vue SFC (via `VueComponentRenderTemplate`). Implement uploads, selects, signature, validation, and submit **inside that SFC** (and its child Vue components).

### 1. Document / file upload — inside Vue

**When:** adjuntar documentación, KYC files, invoices, multi-file packs.

**Do:**

1. Build upload UI in the Vue SFC (child field components OK; e.g. `vue-simple-uploader` posting to the request files API).
2. Use `meta[name=request-id]` → `/api/1.0/requests/{id}/files` (or `PM4ConfigOverrides.postFileEndpoint`) with query `data_name` = field name; write file id(s) into request data / `v-model`; update `PM4ConfigOverrides.requestFiles` when applicable.
3. Validate required files before calling `submit()`.
4. Do **not** fake uploads with checkboxes only.
5. Do **not** add native SB `FileUpload` controls next to the Vue mount.

### 2. Lookup / catalog lists — Collection + Vue select (**mandatory**)

**When:** any `<select>` / dropdown / radio / checkbox group with **more than two** options (leave types, departments, countries, document types, approval decisions with 3+ values, channels, risk bands, …).

**Hard rule — do this BEFORE building the consumer process screen:**

1. `list-collections` — reuse if a catalog already exists.
2. Else create Collection CRUD screens (or reuse `Catalog Option Form` / `Catalog Option Display` when present) with fields at least `code`, `label`, `sort_order`, `active`.
3. `create-collection` (skill `collection-creation-rules`) → keep the **numeric** collection id.
4. Seed rows with `create-collection-records` (skill `collection-test-data`). Empty catalog → empty select.
5. Confirm the auto Data Connector for `/collections/{id}/records` via `list-data-connectors` (same name; if missing, create it — queue may not have run).
6. In the **process Vue SFC**, load options with `ProcessMaker.apiClient.get('collections/{id}/records')` (or the Data Connector), map `data.code` → value and `data.label` → label, and bind a Vue `<select>`. Pass the numeric collection id as a prop from `contentVue` (e.g. `:leave-types-collection-id="2"`).
7. Gateways / BPMN conditions must use the **code** values from the catalog, not free-typed labels.

**Only** binary never-changing enums (Yes/No, True/False) may be hard-coded in the SFC.

Do **not** hard-code multi-option arrays in Vue as the primary source of truth.  
Do **not** add native Screen Builder `FormSelectList` for the main UX.

### 3. Signature — inside Vue

**When:** contract sign-off, acknowledge & sign, client signature.

**Do:**

1. Implement a signature pad in the Vue SFC (canvas or `vue-signature-pad`) and persist a data-URL into request data (e.g. `clientSignature`).
2. Block submit until a non-empty signature exists when signing is mandatory.
3. Do **not** use checkbox-only “I signed” unless the human explicitly asked for a mock.
4. Do **not** add native SB `SignaturePad` next to the Vue mount.

### Page composition pattern

`page.items` should contain the Vue Component Editor only (plus optional thin submit `FormButton` if the Vue mount does not own submit). No FileUpload / SignaturePad / FormSelectList siblings for domain UX.

## Hard don’ts

- Do not put the main UI (forms, layouts, result lists, brand chrome) inline in `contentVue`.
- Do not leave `components` empty when `contentVue` uses a custom/catalog tag.
- Do not ship a screen whose main UI is only native SB controls or HTML viewers.
- Do not omit `VueComponentRenderTemplate` when publishing the screen config.
- Do not reference a catalog/custom tag in `contentVue` without a matching `{name,key}` copied from a real source.
- Do not add a new `@/…` SFC under `examples/` (or elsewhere) without also updating `public-screen-components.json`, rebuilding, and publishing assets.
- Do not put secrets in component config.
- Do not use native SB `FileUpload` / `SignaturePad` / `FormSelectList` (or other predefined controls) to implement upload, signature, or catalog selects — implement them in the Vue SFC.
- Do not ship multi-option selects as hard-coded Vue arrays — create/reuse a Collection, seed it, and wire the numeric id.
- Do not fake uploads or signatures with checkboxes alone.

## Refactoring an existing screen (mandatory parity)

When replacing a traditional Screen Builder screen (or rewriting a Vue one), load skills **`screen-refactor-parity`** and **`screen-vue-fields`** and follow them **before** claiming done.

Minimum bar:

1. Inventory every original field `name`, **full native control `config`**, ancestor `conditionalHide`, `readonly`, computed `property`, watcher (`script_id` **or** Data Connector), and **every nested-screen id** (inventory each nest with `get-screen`).
2. Reimplement **behavior** configs in the Vue SFC (validation, visibility, options/codes, events, data-affecting defaults). UI-only configs (colors, CSS, chrome) may change for redesign — details in `screen-refactor-parity`.
3. Recreate nested screens as a **shared** Vue child SFC reused by every parent that nested that screen (register + import; do not duplicate markup).
4. Put **all** domain variables on request `data`; render editable UI only for originally **visible interactive** controls. Always-hidden / data-only keys (`conditionalHide: "false"`, etc.) must not become new selectors/inputs.
5. **Calcs and watchers live in the Vue SFC** — Vue-only screens do not fire SB `computed` / `watchers` (watched FormButton variables are missing from the compiler). Reimplement calcs in Vue; call backend scripts via `POST scripts/execute/{id}` (`sync: true`) or an equivalent helper (e.g. `executePmScript`); call Data Connectors / Collections from Vue.
6. On `create-screen` / `update-screen` set `computed: []` and `watchers: []` (not `null`). Do **not** copy original SB calc/watcher JSON expecting them to run.
7. Still write the same request-data keys (including legacy trigger values like `search: "1"`) when BPMN or scripts expect them.

## Done checklist

- [ ] Screen exists and id is known
- [ ] Page items use **only** `VueComponentRenderTemplate` for the main UI (no domain native siblings)
- [ ] `contentVue` only mounts named Vue component tag(s)
- [ ] UI implementation lives in the mounted component (not inline in `contentVue`)
- [ ] Multi-option selects use a seeded Collection (+ Data Connector) and fetch by numeric id inside the Vue SFC (not hard-coded option arrays)
- [ ] Upload / signature are implemented **inside** the Vue SFC with working APIs
- [ ] `handlerVue` returns an object and stays thin
- [ ] `components` has real copied `{name,key}` entries for every mounted tag
- [ ] New official `@/…` components are in `public-screen-components.json`
- [ ] `npm run prod` (package-plg) + `vendor:publish --tag=package-plg --force` (core) ran after catalog/SFC changes
- [ ] Screen Builder preview actually renders the mounted component
- [ ] `get-screen` shows the control wired as intended
- [ ] **If refactoring:** skill `screen-refactor-parity` inventory is complete; all original fields, behavior configs, calcs, watchers, and nested screens (shared child SFCs) live in Vue (SB arrays empty)

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
