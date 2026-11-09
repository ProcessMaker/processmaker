# Skill: Collection creation rules

Use before `create-collection` / `update-collection`.

## Required fields

| Field | Notes |
|-------|--------|
| `name` | Unique; `alpha_spaces` |
| `description` | Required |
| `create_screen_id` | FORM screen for create |
| `read_screen_id` | DISPLAY (or FORM) screen for view |
| `update_screen_id` | FORM screen for edit |

Optional: `custom_title` (Mustache for record title), `obfuscate`, `signal_create` / `signal_update` / `signal_delete`, `type` (`NORMAL` default; `RAG` uses seeded RAG screens).

## When to create a Collection (select-list catalogs) — **always for multi-option selects**

**Mandatory** whenever a process/screen select, radio, or checkbox group has **more than two** options. Do this **before** authoring the consumer Vue SFC / process screen:

| Examples | Create Collection? |
|----------|--------------------|
| Leave types, approval decisions (3+), departments, countries, document types, channels, risk bands, product types | **Yes — always** |
| Yes/No, True/False only | No (hard-code in Vue OK) |

Then load those records **inside the process Vue SFC** (`ProcessMaker.apiClient.get('collections/{id}/records')` or the auto Data Connector) and bind a Vue `<select>`. Pass the numeric collection id into the mount props. Do **not** add native Screen Builder `FormSelectList` for the main UX (see skill `screen-creation-rules`). Do **not** leave multi-option lists as hard-coded arrays in Vue.

Typical record fields: `code` (stable value used in BPMN conditions) + `label` (display) + optional `active` / `sort_order`.

## Workflow

1. Prefer reusing existing screens (`list-screens` / Screens MCP). Field names on the create screen become the record `data` keys.
2. If screens are missing, create them with Screens MCP first (skill `screen-creation-rules`), then create the collection.
3. `list-collections` to avoid duplicate names.
4. `create-collection` — keep the returned **numeric id** for select wiring.
5. Seed catalog rows with `create-collection-records` (skill `collection-test-data`).
6. When `package-data-sources` is installed, a Data Connector is auto-created for `/collections/{id}/records` (observer runs `CreateDataSource` synchronously). Discover it with `list-data-connectors` (same name, possibly with `_N` suffix). If missing, `create-data-connector` with `ListAll` → `/api/1.0/collections/{id}/records`.
7. On the consumer Vue SFC, fetch by **numeric** collection id from create/list (MCP does not resolve collection names automatically). Wire the id in screen `contentVue` props.
8. Align BPMN gateway conditions with catalog **`code`** values.

## Record schema

There is no separate DB schema for fields. Record payload is JSON in `data`, shaped by the create/update screens. Listing columns live in `columns` (managed in Designer / `update-collection` when needed).

## Test data

Use `create-collection-records` (skill `collection-test-data`) after the collection exists.

## Pitfalls

1. Screen ids are required even for RAG unless the instance already has `rag-create-screen` / `rag-view-screen` and you still satisfy validation.
2. Creating a collection also creates dynamic tables `collection_{id}` — do not invent table names.
3. Package must be installed (`package-collections`).
4. Forgetting to seed records → empty selects at runtime.
5. Leaving `collectionId` as a name/string or inventing ids → broken selects; always use `list-collections` / create response id.
