# Skill: Boundary Event nodes

Use when attaching timer, message, error, signal, conditional, or escalation handlers to an activity.

## Identity

All are `bpmn:BoundaryEvent` with `attachedToRef` = host activity id. Default `cancelActivity="true"` (interrupting) unless toggled.

| Modeler id | Event definition |
|------------|------------------|
| `processmaker-modeler-boundary-timer-event` | Timer |
| `processmaker-modeler-boundary-message-event` | Message |
| `processmaker-modeler-boundary-error-event` | Error |
| `processmaker-modeler-boundary-signal-event` | Signal |
| `processmaker-modeler-boundary-conditional-event` | Conditional |
| `processmaker-modeler-boundary-escalation-event` | Escalation |

## Common configuration

| Field | Purpose |
|-------|---------|
| `attachedToRef` | Must reference Form/Script/CallActivity/ServiceTask/etc. |
| `cancelActivity` | `true` interrupting (cancels activity); `false` non-interrupting |
| Event-specific | Same timers/messages/signals/errors as intermediate events |

## Example (interrupting timer on a Form Task)

```xml
<bpmn:boundaryEvent id="node_80" name="SLA" attachedToRef="node_10" cancelActivity="true">
  <bpmn:timerEventDefinition>
    <bpmn:timeDuration xsi:type="bpmn:tFormalExpression">PT24H</bpmn:timeDuration>
  </bpmn:timerEventDefinition>
</bpmn:boundaryEvent>
```

## Error boundary

Catches errors thrown from the activity (script failures, error end inside CallActivity, etc.). Configure error name to match thrower, or catch-all per engine rules.

## Data

- Interrupting: activity token cancelled; boundary outgoing path continues with request data
- Non-interrupting: parallel path while activity continues

## Pitfalls

1. Missing `attachedToRef` or attaching to a non-activity
2. Interrupting vs non-interrupting chosen wrong for SLA vs notification paths
3. Error boundary with no matching error name
4. Timer semantics differ on Script Task vs Form Task hosts
5. Always draw an outgoing sequence flow from the boundary event
