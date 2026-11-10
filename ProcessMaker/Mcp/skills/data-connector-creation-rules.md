# Skill: Data Connector creation rules

Use before `create-data-connector` / `update-data-connector`.

## Required fields

| Field | Notes |
|-------|--------|
| `name` | Unique; letters, numbers, spaces, `-_.'` (`alpha_spaces`) |
| `description` | Recommended |
| `type` | `REST` (default for agents) or `SOAP` |
| `authtype` | Depends on `type` (see below) |
| `data_source_category_id` | Use `list-data-connector-categories` |
| `status` | `ACTIVE` or `INACTIVE` (default `ACTIVE`) |

Optional: `api_base_url`, `endpoints`, `credentials`, `debug_mode`.

Prefer **REST**. Only create SOAP when the human explicitly needs WSDL/XSD.

## Auth (`authtype` + `credentials`)

### REST

| authtype | credentials keys |
|----------|------------------|
| `NONE` | (omit / empty) |
| `BASIC` | `username`, `password` |
| `OAUTH2_BEARER` | `token` |
| `OAUTH2_PASSWORD` | `url`, `client_id`, `client_secret`, `username`, `password` |
| `OAUTH2_AUTHORIZATION_CODE` | OAuth code flow keys (`client_id`, `client_secret`, `token_url`, …) — prefer human setup |

### SOAP

`PASSWORD`, `LOCAL_CERTIFICATE`, or `WSDL_FILE` — usually configured in the Designer UI (files on disk). Prefer REST via MCP.

## Endpoints (resources)

`endpoints` is a **map** keyed by `purpose` (resource name). There is no nested CRUD API — send the whole map on create/update.

```json
{
  "listUsers": {
    "purpose": "listUsers",
    "method": "GET",
    "url": "{{__api_base_url__}}/api/users",
    "description": "List users",
    "headers": [],
    "params": [{ "key": "page", "value": "{{page}}", "required": false }],
    "body": "",
    "body_type": "raw",
    "timeout": 0,
    "retry_attempts": 0,
    "retry_wait_time": 5,
    "outboundConfig": [],
    "testData": "{}"
  }
}
```

Rules:

1. Map key **must** equal `purpose`.
2. Every endpoint needs `purpose`, `method` (`GET`|`POST`|`PUT`|`PATCH`|`DELETE`), and `url`.
3. Use `{{__api_base_url__}}` when `api_base_url` is set.
4. Request variables in params/body: `{{variableName}}`.
5. `testData` is a JSON **string** used by the Designer test panel.

## Workflow

1. `list-data-connectors` — avoid duplicates.
2. `list-data-connector-categories` — pick a category id.
3. `create-data-connector` with at least one useful endpoint.
4. Wire into BPMN with skill `data-connector-in-process` (Service Task `package-data-sources/data-source-task-service`).

## Collections (select-list catalogs)

Creating a Collection auto-creates a Data Connector pointed at `/collections/{id}/records` when `package-data-sources` is installed (`CreateDataSource` runs **synchronously** from `CollectionObserver`). Prefer `create-collection` + seed for dropdown catalogs instead of hand-building that connector.

After `create-collection`, always `list-data-connectors` (filter by collection name). If the connector is missing, create one with `ListAll` → `{{__api_base_url__}}/api/1.0/collections/{id}/records` and a non-system category from `list-data-connector-categories` (create a category in Designer if the list is empty).

Process Vue selects should still fetch via `collections/{id}/records` (or this connector). Multi-option selects **must** use Collections — see `collection-creation-rules` and `screen-creation-rules`.

## Pitfalls

1. Do not invent endpoint shapes — copy from `get-data-connector` on a working connector.
2. Never put secrets in skill notes or process descriptions; pass `credentials` only in the tool call.
3. Package must be installed (`package-data-sources`).
