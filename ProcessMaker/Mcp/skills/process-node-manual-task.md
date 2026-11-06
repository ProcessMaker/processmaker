# Skill: Manual Task (`bpmn:ManualTask`)

Use when adding a Manual Task — human acknowledgment with a **DISPLAY** screen (not a data-entry form).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-modeler-manual-task` |
| BPMN type | `bpmn:ManualTask` |
| Default assignment | `requester` |

## Inspectors → attributes

| UI | Attr | Required | Notes |
|----|------|----------|-------|
| Name | `name` | yes | |
| Screen for Input | `pm:screenRef` | **yes** | Screen type **DISPLAY** only |
| Due In | `pm:dueIn` (+ variable flags) | no | |
| Assignment Rules | same as Form Task | practically yes | Same assignment types |
| Notifications | `pm:config` / email package | no | |
| Interstitial | `pm:allowInterstitial`, `pm:interstitialScreenRef` | conditional | If `allowInterstitial="true"`, **`interstitialScreenRef` required** (DISPLAY). See `process-node-start-event`. |
| Element Destination / Conditional Redirect | `pm:*` | no | Same pattern as Form Task |

## Example

```xml
<bpmn:manualTask id="node_20" name="Acknowledge Results"
  pm:screenRef="${screen:results-display}"
  pm:assignment="requester" />
```

## Data in / out

- **In:** request data rendered on DISPLAY screen
- **Out:** user completes/acks the task; little or no field merge (unlike FORM screens)

## Pitfalls

1. Using a FORM screen here is wrong — Modeler filters DISPLAY
2. Still requires `screenRef` (validateScreenRef applies)
3. Do not use Manual Task when you need users to edit and submit fields — use Form Task
4. Prefer `${screen:title}` in git BPMN
