# Skill: Screen refactor parity (traditional → Vue)

Use when **refactoring** an existing ProcessMaker screen into a Vue Component Editor SFC (Arco / design-system restyle, or any “make it nicer” rewrite). Load together with `screen-creation-rules`.

## Hard rule

The refactored screen MUST preserve the original’s **request data contract**, **behavior**, and **user-facing help content**:

1. **Every field / variable** the original writes or reads  
2. **Every computed (calc)** — reimplemented **inside the Vue SFC**  
3. **Every watcher** — backend scripts / Data Connectors invoked **from the Vue SFC**; frontend-only watcher logic also in Vue  
4. **Instructional / help UX** — images, diagrams, tip alerts, “how to find …” copy, and helper links that teach the user how to complete the form (see below)

A prettier UI that drops variables, calcs, watchers, or that help content is **incomplete** and will break BPMN gateways, scripts, emails — or leave users without guidance the original screen provided.

## Why Screen Builder calcs/watchers do not run on Vue-only screens

Traditional SB calcs and watchers are wired by the Screen Builder compiler from **parsed control variables** (e.g. `FormButton` named `search` / `verify`).

On a Vue-only screen (`page.items` = only `VueComponentRenderTemplate`):

- Those FormButton / FormInput names are **not** in the variable list.
- `Watchers.js` **filters out** watchers whose `watching` key is missing → **no `$watch` is mounted**.
- Copying the original `computed` / `watchers` arrays onto `create-screen` / `update-screen` therefore **does not fire** them.

**Do not** rely on SB `computed` / `watchers` JSON for Vue-only refactors. Put the logic in the mounted SFC. Prefer `computed: []` and `watchers: []` on the new screen (never `null`).

## Mandatory inventory (before writing Vue)

From `get-screen` on the **original** id, build a checklist:

| Source | Capture |
|--------|---------|
| Controls | Every `config.name` (incl. nested `contact.phone`, FileUpload `data_name`, button `name` + `event` + `fieldValue`) |
| Control config | **Full** native SB `config` object per control (see next section) — behavior keys must transfer into the Vue SFC |
| Visibility | Every `conditionalHide` on the control **and ancestors** (FormMultiColumn / wrappers); `readonly` / `disabled` |
| Selects | `optionsList` / `dataObject` / Collection / Data Connector source |
| Computed | `property`, `type`, full `formula` |
| Watchers | `watching`, `script_id` **or** data-source / connector config, `input_data`, `output_variable`, sync flags |
| Nested screens | Every nested-screen control (`FormNestedScreen` / equivalent): nested **screen id**, title, where it is mounted, and a full inventory of **that** nested screen (fields, config, calcs, watchers, deeper nests) |
| Help / instructional UX | Every visible `FormImage`, instructional `FormHtmlViewer` / rich HTML (tips, “how to find X”, diagrams, helper links) — purpose, nearby field it supports, asset/URL or extractable image |

Do **not** start the Vue SFC until this inventory exists. Tick every row when the new screen ships — including every nested screen id.

## Nested screens → shared Vue components (mandatory)

Traditional Screen Builder can embed another screen via a nested-screen control. A Vue refactor **must not** drop those embeds.

### Inventory

1. From the parent `get-screen` config, find every nested-screen control and record its **nested screen id** (and label).
2. Call `get-screen` on **each** nested id. Inventory that screen the same way as the parent (fields, full `config`, visibility, calcs, watchers, and any further nests).
3. Note which parents reuse the same nested id — that reuse must survive as a **shared** Vue child.

### Recreate (do not skip)

- Recreate the nested UX inside Vue. Prefer a **dedicated shared SFC** (e.g. `WcpClaimSummaryPanel.vue`) that implements the nested screen’s fields/behavior.
- Register that child in `public-screen-components.json` (own `{name,key}` row) and list it under each parent’s nested `components` when parents mount it via Vue Component Editor; parents that are package SFCs `import` the shared child instead of duplicating markup.
- **Reuse one shared component** wherever the original nested the same screen id. Do **not** copy-paste the nested UI into every parent.
- Parent SFCs mount the shared child with `v-model` / props bound to the same request-data paths the nested screen used.
- Nested screen **calcs / watchers** move into the shared child (or a composable/helper it calls) — same rules as parent refactors (`executePmScript` / Collections; no reliance on SB watcher JSON on the parent Vue-only screen).
- If the nested screen is also used as a **standalone** task/DISPLAY screen, that published screen should mount the **same** shared SFC (thin `VueComponentRenderTemplate`), not a second divergent implementation.

### Optional chrome-only exception

Header/footer/brand-only nests may be restyled or folded into parent chrome **only** when the human agrees. Domain fields, summaries, selectors, and any Mustache-bound data inside nests are **not** optional — they must appear via the shared child (or equivalent bindings).

### Anti-patterns

- Refactoring the parent shell and leaving nested screens as missing blank regions
- Inlining the same nested UI three times instead of one shared SFC
- Creating a new Arco parent screen while the nested screen id still points at the old traditional screen without a Vue equivalent
- Treating “nested” as out of scope for `screen-refactor-parity`

## Native SB control `config` → Vue (mandatory)

For **every** native Screen Builder control on the original screen, open its `config` (and relevant `inspector` field values) and map behavior into the Vue component. A redesign may change **look**; it must not drop **behavior**.

### Must preserve (behavior / data contract)

Review and reimplement in the Vue SFC (props, `v-model`, validators, `v-if`, methods — not as SB inspector JSON):

| Config area | Typical SB keys | Vue expectation |
|-------------|-----------------|-----------------|
| Variable | `name`, `data_name` | Same request-data key |
| Field type / format | `type`, `dataFormat`, `rows`, mask/currency | Same input semantics |
| Validation | `validation`, required, min/max, regex | Same rules (Arco / HTML5 / custom) |
| Visibility / state | `conditionalHide`, `readonly`, `disabled`, `hidden` | Same show/hide and editability |
| Defaults / placeholders (content) | `placeholder`, `helper`, default value when it affects data | Same defaults where they change submitted data |
| Options / data source | `optionsList`, `dataObject`, Collection / connector ids, value/content keys | Same codes and sources |
| Buttons / events | `event`, `fieldValue`, `variant` only if it changes payload | Same submit/trigger payload |
| File / signature | upload `data_name`, accepted types, signature variable | Same persistence keys and constraints |
| Rich / HTML content with Mustache | bound `{{…}}` variables | Same data still on request `data` |

Walk **every** control — including nested cells in `FormMultiColumn` / loops / nested screens — and tick each behavior key.

### May change (UI redesign only)

Safe to restyle or drop when the redesign replaces native chrome:

- Colors, fonts, spacing, borders, shadows, icons, button chrome
- `customCss` / `customCssSelector` / theme classes
- Label wording polish **only** when the human asked for copy refresh (default: keep label meaning)
- Layout wrappers (columns → Arco grid) **without** dropping fields or visibility rules
- Presentation of instructional help (collapse into accordion / tooltip / compact callout, smaller images) — **not** deletion of the teaching content itself

**Do not** treat “redesign” as permission to skip validation, `conditionalHide`, `readonly`, option codes, `fieldValue`, variable names, or instructional help (images / how-to copy / tip alerts / helper links).

### Anti-pattern

Reading only `config.name` and rebuilding a pretty input — while ignoring `validation`, `conditionalHide`, `readonly`, `optionsList`, `fieldValue`, defaults, or file constraints — is a **parity bug**.

## Instructional / help content (do not discard)

Traditional screens often teach the user **how to fill a field** with images and HTML that are **not** request-data variables. A Vue redesign must keep that teaching — restyled and optionally condensed — not strip it because it is “not a field.”

### Inventory and keep

From the original `get-screen` config, capture every visible help block, including:

| Original control | Examples | Vue expectation |
|------------------|----------|-----------------|
| `FormImage` | Diagram of where a warranty / serial / EGG ID appears on a product or cover sheet | Same teaching image(s) (or equivalent cropped/optimized asset) next to the field they support |
| Instructional `FormHtmlViewer` / rich HTML | “If pre-2016, leave blank”, “12-digit number in the yellow box…”, dome serial / barcode guidance | Same meaning in a redesign-friendly callout, caption, or accordion |
| Helper links | “Not sure what size…?” external docs | Keep the link (and intent) near the related control |

**Classic keep:** WCP Product Information (screen 72) — images + copy that explain how to find the EGG ID / serial. Dropping those in an Arco rewrite is a **parity bug** even if every `config.name` still submits.

### Redesign allowed (condense, do not delete)

- Move long image+text stacks into a compact “How to find your code” accordion / drawer / modal next to the input  
- Crop, compress, or re-export base64 `FormImage` assets into package static files  
- Merge several tip paragraphs into one short callout **if** every original fact remains  
- Restyle alert boxes (`alert-info`, muted captions) into Arco / design-system equivalents  

### Anti-patterns

- Shipping only the inputs and calling decorative/help images “chrome” that can be dropped  
- Keeping helper text but dropping the diagram the text refers to (or vice versa)  
- Replacing a how-to image with a vague icon that no longer shows *where* the code is  
- Treating absence of a `config.name` on `FormImage` / HTML as “out of scope for parity”

## Visibility / readonly (do not invent UI)

Screen Builder `conditionalHide` means: **show when the expression is true** (“hidden until true”).

| Original | Vue behavior |
|----------|--------------|
| Control or **ancestor** has `conditionalHide: "false"` (literal) | Expression never true → **always hidden**. Keep the key in `data` / `ensureKeys` if watchers/scripts need it; **do not** render an editable control. |
| `conditionalHide` expression (e.g. `contact_found`, `edit_user_information`) | Mirror with `v-if` / `v-show` using the same request-data conditions. |
| `readonly: true` / disabled | Do not expose as a free editable input; omit from UI or show read-only if the original was visible. |
| Field present only for watcher/`input_data` payloads | Data-only: initialize on `data`, no user control unless the original showed one. |

**Classic failure:** WCP claim (screen 60) — top-level `country` and `product_registration` exist for HubSpot watcher payloads, are `readonly`, and sit under a parent with `conditionalHide: "false"`. The user only sees **email**. A Vue rewrite that adds Country / Product registration selectors is a **parity bug**.

Classify every inventoried control before coding:

1. **Visible interactive** → bind in the Vue UI  
2. **Visible read-only** → display only  
3. **Hidden / data-only** → `ensureKeys` only (no selector/input)

## Fields / variables

- Initialize **all** original variable names on `v-model` / `data` (`ensureKeys` / `$set`), including former watcher triggers (`search`, `verify`, …) and submit discriminators (`next_task`, `approve`, …) — **including hidden data-only keys**.
- Bind in the Vue UI only controls that were **visible and interactive** on the original (see Visibility table). Hidden/readonly data keys stay off the form chrome.
- Preserve nested objects (`contact.*`, `shipping_info.*`, `product_registration.*` object paths when the original used them).
- Preserve button semantics: same `name` keys and the same values the original `fieldValue` / submit payload used (`egg` / `accessory` / `register` / `Yes` / `No` / …).
- Optional display-only HTML can be restyled, but any Mustache-bound **data** it showed must still be available on request data.
- Instructional HTML / images (how-to find a code, tip alerts, helper links) must still appear in the Vue UI — restyled or condensed is fine; omitted is not (see Instructional / help content).

## Computed (calcs) → Vue SFC

Reimplement **every** original computed `property` in the Vue SFC. Write derived values back onto request `data` when BPMN, emails, later screens, or scripts expect those keys.

| Kind | Examples | Where it lives after refactor |
|------|----------|-------------------------------|
| **Frontend / DOM** | `window.*`, `document.*`, `location.*`, `querySelector`, `setTimeout` redirects | Vue methods / `watch` / `mounted` |
| **Derived request data** | `can_search`, `usa_contact`, `otp_hash`, `dealer_copy`, validation objects | Vue `computed` + `$set` onto `data` |
| **Expression labels** | `task_title` | Set in Vue (`created` / computed) |

Never drop a computed `property` that BPMN, emails, or other screens read.

## Watchers → Vue SFC

Inspect each original watcher source:

| Source | Action |
|--------|--------|
| **Script (backend)** — PHP/JS executor, HubSpot, APIs, secrets, `getenv` | Call from Vue with the **same** `script_id` and the same payload fields as `input_data`. Write the result to the same `output_variable` on request `data`. Prefer sync execute: `POST scripts/execute/{script_id}` with `{ data, config, sync: true }`. Reuse helpers such as package-plg `executePmScript` (`wcp/wcpCatalog.js`) when available. **Do not** leave secrets in the Vue SFC — keep them in the script. |
| **Data Connector / data source** | Call the connector / collections API from Vue (same endpoint / collection id). Do not hard-code the connector response as the primary source of truth. |
| **Frontend-only** | Reimplement in Vue methods / watchers. |

### Trigger hygiene

- User actions in Vue replace SB FormButtons: e.g. Search → method that runs script `1` and sets `search_contact`.
- Still set legacy trigger keys on `data` when downstream assets read them (`search: "1"`, `verify: "1"`, `edit_user_information_decision: "Yes"`), but **do not** expect SB watchers to react.
- Never overwrite domain values (`would_you_like`, `assigned_dealer`, `approve`) with timestamps.
- Match original button `fieldValue` when a script or BPMN expects a specific value (`"1"`, `"Yes"`, …).

### Optional local mock

A temporary `parity_mock` (or similar) flag in the SFC is fine for Preview without HubSpot / external APIs. Live path must still call the real script / connector.

## Screen JSON packaging

New/updated screen MUST include:

1. `VueComponentRenderTemplate` mount (thin `contentVue`)  
2. **`computed`: `[]`** — logic lives in the Vue SFC (not a copy of the original SB calcs)  
3. **`watchers`: `[]`** — scripts/connectors invoked from Vue (not SB watcher JSON)  
4. Same `type` (FORM vs DISPLAY) unless the human explicitly changes the task type  

Reference pattern: WCP claim form (`WcpWarrantyClaimForm`) — calcs in Vue `computed`/`watch`; HubSpot scripts via `executePmScript`.

## Done checklist (refactor)

- [ ] Inventory table exists (fields, **full per-control `config`**, calcs, watchers, **help images/HTML**) **including ancestor `conditionalHide` + `readonly`**
- [ ] Every nested-screen id is inventoried (`get-screen`) and recreated as a **shared** Vue SFC reused by all parents that nested it
- [ ] Every native SB control’s **behavior** configs are reimplemented in the Vue SFC (validation, visibility, options, events, defaults that affect data)
- [ ] Instructional images, tip copy, and helper links are present in the Vue UI (restyled/condensed OK; not omitted)
- [ ] Only UI-only configs (colors / CSS / chrome) were intentionally dropped for the redesign — **not** teaching content
- [ ] Every original `config.name` is on request `data`; UI only for visible interactive controls
- [ ] No always-hidden / data-only fields rendered as editable selects/inputs (literal `conditionalHide: "false"`)
- [ ] Visibility / branching matches original business rules
- [ ] Multi-option selects use Collections (see `screen-creation-rules`) with the **same codes** BPMN already uses
- [ ] Every original computed `property` is implemented in the Vue SFC and written to request `data` when needed
- [ ] Every backend / connector watcher is invoked from Vue with the same `script_id` / endpoint and `output_variable`
- [ ] New screen `computed` / `watchers` are `[]` (not relying on SB runtime for Vue-only mounts)
- [ ] Spot-check: Preview / task / web entry produces the same request data keys the process scripts expect

## Anti-patterns

- Shipping an Arco shell with a subset of inputs “for polish”
- Mapping only `config.name` and ignoring other SB `config` keys (validation, hide rules, options, `fieldValue`, …)
- Showing every `config.name` as an editable control (ignores `conditionalHide` / `readonly` / ancestor wrappers)
- Treating presence in `get-screen` config as “must appear in the Vue form chrome”
- Dropping `FormImage` / instructional HTML (warranty-code diagrams, tip alerts, helper links) because they have no `config.name`
- Skipping nested screens or duplicating nested UI instead of a shared child SFC
- Copying original `computed` / `watchers` onto the new screen and assuming they will run on a Vue-only config
- Mutating `search` / `verify` only, expecting Screen Builder watchers to fire
- Replacing a HubSpot/API script with a hard-coded Vue mock as the **only** production path
- Putting secrets, tokens, or `getenv` equivalents into the Vue SFC
- Assuming nested header/footer screens are the only missing pieces when domain fields were skipped
