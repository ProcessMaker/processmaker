# Skill: UiPath connector

Use when adding a UiPath / RPA Service Task. Package is **`package-rpa`** (not a separate uipath package).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-communication-rpa` |
| Label | UiPath |
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `package-rpa/processmaker-communication-rpa` |
| Config | `pm:config` (JSON string) |
| Package | `package-rpa` |

Optional: `pm:errorHandling` (`timeout`, `retry_attempts`, `retry_wait_time`).

## Discover ids

- Folder / process / machine / bot objects from UiPath integration APIs exposed by the package.
- Keep at least the Ids the runtime needs; do not invent them.

## `pm:config` shape

```json
{
  "selectedFolder": { "id": 1, "name": "Default" },
  "selectedProcess": { "Id": "proc-1", "Name": "MyBot" },
  "selectedMachine": { "Id": "m1", "Name": "Host" },
  "selectedBot": { "Id": "b1", "Name": "Bot" },
  "inboundConfig": [
    { "key": "inArg", "value": "order_id" }
  ],
  "dataMapping": [
    { "key": "rpa_out", "value": "", "format": "dotNotation" }
  ]
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `selectedFolder` | yes | Object from folder select |
| `selectedProcess` | yes | UiPath process object |
| `selectedMachine` | yes | |
| `selectedBot` | yes | |
| `inboundConfig` | no | Process input arg ← request var (`key` / `value`) |
| `dataMapping` | no | Response → request var (`key` = request var, `value` = response path) |

## Example BPMN

```xml
<bpmn:serviceTask id="node_rpa" name="UiPath"
  implementation="package-rpa/processmaker-communication-rpa"
  pm:config="{&quot;selectedFolder&quot;:{&quot;id&quot;:1,&quot;name&quot;:&quot;Default&quot;},&quot;selectedProcess&quot;:{&quot;Id&quot;:&quot;proc-1&quot;,&quot;Name&quot;:&quot;MyBot&quot;},&quot;selectedMachine&quot;:{&quot;Id&quot;:&quot;m1&quot;,&quot;Name&quot;:&quot;Host&quot;},&quot;selectedBot&quot;:{&quot;Id&quot;:&quot;b1&quot;,&quot;Name&quot;:&quot;Bot&quot;},&quot;inboundConfig&quot;:[],&quot;dataMapping&quot;:[]}" />
```

## Pitfalls

1. Implementation string is `package-rpa/...`, never invent `connector-uipath/...`.
2. Selection fields are objects from the UiPath API, not plain name strings.
3. Package + UiPath orchestrator credentials must be configured.
