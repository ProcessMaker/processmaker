# Skill: Collection test data

Use when seeding sample/demo rows with `create-collection-records` (or listing with `list-collection-records`).

## Payload

```json
{
  "collection_id": 5,
  "records": [
    { "first_name": "Ada", "last_name": "Lovelace" },
    { "first_name": "Grace", "last_name": "Hopper" }
  ]
}
```

Or a single object:

```json
{
  "collection_id": 5,
  "data": { "first_name": "Ada", "last_name": "Lovelace" }
}
```

Each record becomes `{ "data": { ... } }` in `collection_{id}`.

## How to choose keys

1. `get-collection` → note `create_screen_id`.
2. Screens MCP `get-screen` on that id → read field `name` / variable names from config (or Vue component `v-model` bindings).
3. Use those exact keys in each record object.
4. If unsure, `list-collection-records` on an existing populated collection and copy the `data` shape.

For **select-list catalogs**, seed at least the values the process screens will show (e.g. `{ "code": "PERSONAL", "label": "Personal" }`). Empty collections → empty `FormSelectList` dropdowns.

## Limits

- Prefer small batches for demos (tens of rows, not thousands).
- No field-level server validation against the screen — bad keys simply sit unused in JSON.
- Caller needs create permission on that collection (admins pass).

## Pitfalls

1. Do not put records under a top-level key other than the field names themselves inside each object.
2. Truncating / wiping production collections is out of scope for this skill — only add test rows when the human asked for sample data.
