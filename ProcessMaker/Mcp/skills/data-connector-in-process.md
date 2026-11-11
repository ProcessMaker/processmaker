# Skill: Data Connector in a process

Use when wiring a Data Connector into BPMN (Service Task or callback Call Activity) or when `create-process` / `update-process` needs an external API call.

## Identity

| | Service Task (sync) | Call Activity (callback) |
|--|--|--|
| Modeler id | `data-source-task-service` | `data-source-call-activity` |
| BPMN type | `bpmn:ServiceTask` | `bpmn:CallActivity` |
| Marker | `implementation=package-data-sources/data-source-task-service` | `calledElement=data_source_callback-data_source_sub_process` |
| Config | `pm:config` | `pm:config` |

Prefer the Service Task unless the human needs async callback. Toggling `callback: true` in the modeler **replaces** the node type.

Optional: `pm:errorHandling` (`timeout`, `retry_attempts`, `retry_wait_time`).

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
  "type": "",
  "dataMapping": [
    { "key": "users", "value": "data" },
    { "key": "httpStatus", "value": "$status" }
  ],
  "outboundConfig": [
    { "type": "PARAM", "key": "page", "value": "{{page}}" }
  ],
  "callback": false,
  "callback_url": "callback_url",
  "callback_variable": "callback",
  "callback_methods": ["POST"],
  "callback_data_types": ["FORM"],
  "callback_authentication": null,
  "callback_authentication_username": "",
  "callback_authentication_password": "",
  "callback_whitelist": []
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `dataSource` | yes | Numeric Data Connector id |
| `endpoint` | yes | Endpoint `purpose` string |
| `type` | auto | Connector type (ServiceTask); may be empty |
| `dataMapping` | usually | `key` = request var, `value` = response path (`$status` for HTTP status) |
| `outboundConfig` | optional | `{ type: PARAM\|HEADER\|BODY, key, value, format? }` |
| `callback` | optional | `true` → Call Activity callback flow |
| `callback_url` | if callback | Request var that receives callback URL |
| `callback_variable` | if callback | Where callback payload is stored |
| `callback_methods` + `callback_data_types` | if callback | Parallel arrays (e.g. `POST` + `FORM`) |
| `callback_authentication` | optional | `null` or `basic` (+ username/password) |
| `callback_whitelist` | optional | IP allow list; empty = all |

## Example BPMN (sync)

```xml
<bpmn:serviceTask id="node_dc_1" name="Fetch Users"
  implementation="package-data-sources/data-source-task-service"
  pm:config="{&quot;dataSource&quot;:12,&quot;endpoint&quot;:&quot;listUsers&quot;,&quot;dataMapping&quot;:[{&quot;key&quot;:&quot;users&quot;,&quot;value&quot;:&quot;data&quot;}],&quot;outboundConfig&quot;:[],&quot;callback&quot;:false}" />
```

## Example BPMN (callback)

```xml
<bpmn:callActivity id="node_dc_cb" name="Data Connector"
  calledElement="data_source_callback-data_source_sub_process"
  pm:config="{&quot;dataSource&quot;:12,&quot;endpoint&quot;:&quot;asyncJob&quot;,&quot;dataMapping&quot;:[],&quot;outboundConfig&quot;:[],&quot;callback&quot;:true,&quot;callback_url&quot;:&quot;callback_url&quot;,&quot;callback_variable&quot;:&quot;callback&quot;,&quot;callback_methods&quot;:[&quot;POST&quot;],&quot;callback_data_types&quot;:[&quot;FORM&quot;],&quot;callback_authentication&quot;:null,&quot;callback_whitelist&quot;:[]}" />
```

Remember: every `sequenceFlow` must also appear as `<bpmn:outgoing>` / `<bpmn:incoming>` on the nodes (see `process-creation-rules` / `process-validation`).

## Screen watchers

Screens can call the same connector via watchers. Prefer configuring watchers in Screens MCP; still use real `dataSource` id + endpoint purpose.

## Pitfalls

1. Wrong `implementation` / `calledElement` → connector never runs.
2. `dataSource` must be the id, not the name.
3. `endpoint` must match a key/`purpose` on that connector.
4. Ensure `package-data-sources` is installed on the instance.
5. Callback mode must use the Call Activity marker above, not a plain Service Task.
