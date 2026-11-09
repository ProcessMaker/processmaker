# Skill: Data Connector in a process

Use when wiring a Data Connector into BPMN (Service Task) or when `create-process` / `update-process` needs an external API call.

## Identity

| | |
|--|--|
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `package-data-sources/data-source-task-service` |
| Config attribute | `pm:config` (JSON) |

Optional callback mode uses Call Activity `data_source_callback-data_source_sub_process` — prefer the Service Task unless the human needs async callback.

## Discover ids

1. Call Data Connectors MCP: `list-data-connectors` / `get-data-connector`.
2. Note numeric `id` and endpoint `purpose` keys from `endpoints`.
3. Put those into `pm:config`.

Do **not** invent connector ids or purpose names.

## `pm:config` shape

```json
{
  "dataSource": 12,
  "endpoint": "listUsers",
  "dataMapping": [
    { "key": "users", "value": "data" },
    { "key": "httpStatus", "value": "$status" }
  ],
  "outboundConfig": [
    { "type": "PARAM", "key": "page", "value": "{{page}}" }
  ],
  "callback": false
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `dataSource` | yes | Numeric Data Connector id |
| `endpoint` | yes | Endpoint `purpose` string |
| `dataMapping` | usually | Map response paths into request data (`key` = request var, `value` = response path; `$status` for HTTP status) |
| `outboundConfig` | optional | Override PARAM / HEADER / BODY values at runtime |
| `callback` | optional | `true` switches to callback Call Activity flow |

## Example BPMN fragment

```xml
<bpmn:serviceTask id="node_dc_1" name="Fetch Users"
  implementation="package-data-sources/data-source-task-service"
  pm:config="{&quot;dataSource&quot;:12,&quot;endpoint&quot;:&quot;listUsers&quot;,&quot;dataMapping&quot;:[{&quot;key&quot;:&quot;users&quot;,&quot;value&quot;:&quot;data&quot;}],&quot;outboundConfig&quot;:[],&quot;callback&quot;:false}" />
```

Remember: every `sequenceFlow` must also appear as `<bpmn:outgoing>` / `<bpmn:incoming>` on the nodes (see `process-creation-rules` / `process-validation`).

## Screen watchers

Screens can call the same connector via watchers (`script_key` / watcher type for data sources). Prefer configuring watchers in Screens MCP; still use real `dataSource` id + endpoint purpose.

## Pitfalls

1. Wrong `implementation` string → connector never runs.
2. `dataSource` must be the id, not the name.
3. `endpoint` must match a key/`purpose` on that connector.
4. Ensure `package-data-sources` is installed on the instance.
