# Skill: Screen Vue domain fields

Use when building or refactoring ProcessMaker screens with uploads, catalog selects, signatures, or any domain UX that must live inside a Vue Component Editor SFC. Load together with `screen-creation-rules`. When **refactoring** a traditional screen → Vue, also load `screen-refactor-parity`.

## Hard rule

`page.items` = one `VueComponentRenderTemplate` mounting a registered SFC.  
**Do not** add native `FileUpload`, `SignaturePad`, or `FormSelectList` for the main UX.

| Need | Implement in Vue SFC |
|------|----------------------|
| Upload docs | Uploader (e.g. `vue-simple-uploader`) → `/api/1.0/requests/{id}/files` + `data_name`; store file ids on request data |
| Catalog selects (**>2 options**) | **Collection + seed + Data Connector** → `ProcessMaker.apiClient.get('collections/{id}/records')` → `<select>` in Vue. Pass numeric collection id as mount prop. Never hard-code multi-option arrays. |
| Binary enums only | Yes/No may be hard-coded |
| Signature | Canvas / signature pad → persist data-URL on request data (e.g. `clientSignature`) |

## Refactor parity (mandatory)

Do **not** ship a prettier subset of the original form. Details live in skill `screen-refactor-parity`.

1. Inventory original `get-screen`: every control `name`, **full native `config`**, **ancestor `conditionalHide`**, `readonly`, computed `property`, watcher (`script_id` or Data Connector), button `fieldValue`s, and **every nested-screen id** (then `get-screen` each nest).
2. Carry **behavior** configs into the Vue SFC (validation, visibility, options/codes, events, defaults that affect data). UI-only keys (colors, CSS, chrome) may change for redesign — see `screen-refactor-parity`.
3. **Nested screens** — recreate as a **shared** child Vue SFC; parents that nested the same id must `import` / mount that same component (do not duplicate). Details in `screen-refactor-parity`.
4. Vue SFC initializes **all** variables on `data`. Render editable UI **only** for controls that were visible and interactive on the original. Literal `conditionalHide: "false"` (or always-hidden ancestors) → data-only keys, no selector/input (e.g. WCP claim `country` / `product_registration` vs visible `email`).
5. **Calcs** — reimplement every computed `property` in the Vue SFC; `$set` onto request `data` when BPMN/emails need the key.
6. **Watchers**
   - Backend script / connector → call from Vue (`POST scripts/execute/{script_id}` with `sync: true`, or Collections/Data Connector APIs). Write to the same `output_variable`. Keep secrets in the script, not the SFC. Prefer helpers such as package-plg `executePmScript` when available.
   - Frontend-only logic (`window` / `document` / redirects) → Vue methods / `watch`.
7. Vue-only screens **do not** run SB `computed` / `watchers` (compiler never registers FormButton watch keys). Set `computed: []` and `watchers: []` on create/update — do not copy original SB arrays expecting them to fire.
8. Still set legacy trigger keys on `data` when needed (`search: "1"`, …); never wipe domain values with timestamps.

Reference: `WcpWarrantyClaimForm` + `executePmScript` in package-plg.

## Order of work

1. **Catalogs first** — for every multi-option select: `list-collections` / `create-collection` + `create-collection-records`; confirm Data Connector (`list-data-connectors`).
2. If refactoring: finish the parity inventory first (`screen-refactor-parity`).
3. Implement full UX + calcs + script/connector calls in package-plg (or `custom://`) Vue SFC + register/build/publish; fetch options by numeric collection id.
4. `create-screen` / `update-screen` with **only** the Vue mount (props include collection ids) and empty `computed` / `watchers`.
5. Wire numeric `pm:screenRef`; BPMN conditions use catalog **codes**.

## Done when

- [ ] No native FileUpload / SignaturePad / FormSelectList siblings on the FORM
- [ ] Multi-option selects backed by seeded Collections (not hard-coded arrays)
- [ ] Upload/sign/select work inside the Vue component
- [ ] BPMN has numeric screen ids and catalog codes in gateway conditions
- [ ] Refactors: every original field/`config` behavior/calc/watcher (and nested screens as shared child SFCs) lives in Vue per `screen-refactor-parity`
