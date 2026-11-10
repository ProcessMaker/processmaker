# Skill: End Event nodes

Use when adding or configuring End, Terminate, Message End, Signal End, or Error End.

## Modeler ↔ BPMN

All are `bpmn:EndEvent`. Subtype = event definition.

| Modeler id | Event definition | Behavior |
|------------|------------------|----------|
| `processmaker-modeler-end-event` | none | Completes this token path |
| `processmaker-modeler-terminate-end-event` | `TerminateEventDefinition` | Cancels **all** tokens in the instance |
| `processmaker-modeler-message-end-event` | `MessageEventDefinition` | Throws message, then ends |
| `processmaker-modeler-signal-end-event` | `SignalEventDefinition` | Throws signal, then ends |
| `processmaker-modeler-error-end-event` | `ErrorEventDefinition` | Throws named error |

## Shared ProcessMaker inspectors

| UI | Attr | Purpose |
|----|------|---------|
| Name | `name` | Label |
| Summary Screen | `pm:screenRef` | **DISPLAY** screen for completed-request summary |
| Element Destination | `pm:elementDestination` | Post-completion navigation JSON |
| Signal Payload (signal end) | signal `config` | `NONE` / `ALL_REQUEST_DATA` / `REQUEST_VARIABLE` |

Summary screen is UX only — the request still completes without it.

## Plain end

```xml
<bpmn:endEvent id="node_99" name="End" pm:screenRef="${screen:request-summary}" />
```

## Terminate

Use when any parallel path finishing should cancel the whole request. Do not use as the default “happy path” end if other tokens must finish.

## Signal / Message end

- Must reference an existing signal/message in `bpmn:definitions`
- Payload misconfig → empty or wrong data for catchers
- Message end may include data inputs for the payload

## Error end

Sets `errorRef.name`. Pair with a Boundary Error Event on a parent activity or Call Activity when catching.

## Data

- **In:** full request data available to summary DISPLAY screen
- **Out:** request status completed (or cancelled if terminate); no further tokens on that path

## Checklist

- [ ] Every executable path reaches an end (or terminate)
- [ ] Terminate vs normal end chosen intentionally
- [ ] Summary screen type is DISPLAY when set
- [ ] Signal/message/error refs exist and match catchers
