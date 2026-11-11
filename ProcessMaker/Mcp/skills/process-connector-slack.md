# Skill: Slack Notification connector

Use when adding a Slack Notification Service Task (`connector-slack`).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-communication-slack` |
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `connector-slack/processmaker-communication-slack` |
| Config | `pm:config` (JSON string) |
| Package | `connector-slack` |

## Discover ids

- Channels: instance API `/connector-slack/get-channels` (requires Slack integration configured).
- Put the **channel object** returned by the API into `channel` (not a bare string id).

## `pm:config` shape

```json
{
  "channel": { "id": "C01234567", "name": "general" },
  "textBody": "Case done: {{case_number}}"
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `channel` | yes | Object from channel select (`id`, `name`, …) |
| `textBody` | yes | Message body; Mustache OK |

## Example BPMN

```xml
<bpmn:serviceTask id="node_slack" name="Slack Notification"
  implementation="connector-slack/processmaker-communication-slack"
  pm:config="{&quot;channel&quot;:{&quot;id&quot;:&quot;C012&quot;,&quot;name&quot;:&quot;general&quot;},&quot;textBody&quot;:&quot;Done: {{case}}&quot;}" />
```

## Pitfalls

1. `channel` must be the selected object, not only `"C012"`.
2. Slack credentials / package must be configured on the instance.
3. Do not invent channel ids.
