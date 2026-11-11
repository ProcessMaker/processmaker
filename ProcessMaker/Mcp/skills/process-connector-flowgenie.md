# Skill: FlowGenie connector

Use when adding a FlowGenie Service Task (`package-ai`).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-ai-task` |
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `package-ai/processmaker-ai-task` |
| Config | `pm:config` (JSON string) |
| Package | `package-ai` |

Do **not** confuse with designer helper node `processmaker-ai-assistant` / `package-ai/processmaker-ai-assistant` (empty config; not a runnable FlowGenie).

## Discover / create assets

1. Prefer **FlowGenies MCP** (`list-flow-genies` / `create-flow-genie` / `get-flow-genie`). Load skill `flowgenie-creation-rules` on that server.
2. Put the numeric asset id in `flow_genie_id`.
3. Do not invent genie ids.

## `pm:config` shape (preferred)

```json
{
  "flow_genie_id": 7,
  "output_variable": "genie_result"
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `flow_genie_id` | yes | FlowGenie asset id |
| `output_variable` | no | Request var for output |

### Legacy (avoid for new processes)

Older configs may include `prompt`, `file`, `response_format` (`text`|`json`|`table`). Prefer selecting a FlowGenie asset instead.

## Example BPMN

```xml
<bpmn:serviceTask id="node_fg" name="FlowGenie"
  implementation="package-ai/processmaker-ai-task"
  pm:config="{&quot;flow_genie_id&quot;:7,&quot;output_variable&quot;:&quot;result&quot;}" />
```

## Validation

Modeler `validateFlowGenieRef` requires `flow_genie_id` when `implementation` is `package-ai/processmaker-ai-task`. MCP `validate-process` does not yet enforce this — still set a real id from FlowGenies MCP.

## Pitfalls

1. Empty initial `config` is invalid until `flow_genie_id` is set.
2. Wrong implementation string → genie never runs.
3. `package-ai` must be installed.
4. Create/configure the asset (instructions, model, output_type) before wiring BPMN.
