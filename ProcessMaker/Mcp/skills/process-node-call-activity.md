# Skill: Call Activity / Sub Process (`bpmn:CallActivity`)

Use when embedding another Process as a sub-process.

## Identity

| | |
|--|--|
| Modeler id | `processmaker-modeler-call-activity` |
| BPMN type | `bpmn:CallActivity` |
| Defaults | `calledElement=""`, `config="{}"` |

Packages may register alternate CallActivity nodes (e.g. Data Connector `data-source-call-activity`, Actions By Email).

## Inspectors → attributes

| UI | Attr | Required | Notes |
|----|------|----------|-------|
| Name | `name` | yes | |
| Process | `calledElement` (+ JSON in `pm:config`) | **yes** | Target process definition; portable `${process:name}` |
| Start Sub Process As | `pm:assignment` (+ users/groups) | no | Who starts the child request |

### Assignment options (Call Activity)

| Value | Label |
|-------|-------|
| `""` | Anonymous |
| `requester` | Requester |
| `user_group` | Users / Groups |
| `previous_task_assignee` | Previous Task Assignee |
| `process_variable` | Process Variable |

Also supports interstitial / validations / `errorHandling` / custom icon via moddle.

## Example

```xml
<bpmn:callActivity id="node_40" name="Upload Transcript"
  calledElement="${process:upload-transcript}"
  pm:config="{}"
  pm:assignment="requester" />
```

On deploy, `calledElement` becomes `{bpmnProcessId}-{dbId}` (e.g. `ProcessId-34`). On pull, PLG restores `${process:name}`. Nested `pm:config.processId` may stay numeric — that is expected; deploy uses the XML `calledElement` attribute.

## Data in / out

- **In:** parent request data mapped into child (per config / engine defaults)
- **Out:** child completion maps selected data back to parent; parent token continues

## Pitfalls

1. Empty `calledElement` — sub-process never resolves
2. Publish **child process first**, then parent
3. Circular CallActivity graphs
4. Anonymous vs requester starter changes permissions inside the child
5. Do not point CallActivity at a Web Entry–only start that cannot be started as a sub-process
6. Prefer `${process:exact-process-name}` matching the Process `name` (and BPMN filename basename in PLG)
