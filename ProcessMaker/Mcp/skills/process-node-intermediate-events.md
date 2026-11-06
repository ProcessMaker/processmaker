# Skill: Intermediate Event nodes

Use when adding intermediate timer, message, signal, or conditional catch/throw events.

## Modeler ↔ BPMN

| Modeler id | BPMN | Definition |
|------------|------|------------|
| `processmaker-modeler-intermediate-catch-timer-event` | `IntermediateCatchEvent` | Timer (`timeDuration` default; also date/cycle) |
| `processmaker-modeler-intermediate-message-catch-event` | `IntermediateCatchEvent` | Message |
| `processmaker-modeler-intermediate-message-throw-event` | `IntermediateThrowEvent` | Message |
| `processmaker-modeler-intermediate-signal-catch-event` | `IntermediateCatchEvent` | Signal |
| `processmaker-modeler-intermediate-signal-throw-event` | `IntermediateThrowEvent` | Signal |
| `processmaker-modeler-intermediate-conditional-catch-event` | `IntermediateCatchEvent` | Conditional |

## Timer catch

```xml
<bpmn:intermediateCatchEvent id="node_70" name="Wait 1h">
  <bpmn:timerEventDefinition>
    <bpmn:timeDuration xsi:type="bpmn:tFormalExpression">PT1H</bpmn:timeDuration>
  </bpmn:timerEventDefinition>
</bpmn:intermediateCatchEvent>
```

Inspector: IntermediateTimer → duration | date | cycle. Do not confuse with **start** timer (`timeCycle`).

**Data:** none — delays the token.

## Message catch

| UI | Attr | Purpose |
|----|------|---------|
| Message | `messageRef` | Which message to wait for |
| Allowed User / Group | `pm:allowedUsers`, `pm:allowedGroups` | Who may complete/trigger |
| Whitelist | `pm:whitelist` | IP/domain allowlist |

**Data in:** message payload → request (see `variableName` on definition).

## Message throw

Select message + configure data inputs for the outgoing payload.

## Signal catch / throw

- Catch: signal selector + request variable for payload storage
- Throw: signal selector + Signal Payload (`NONE` / `ALL_REQUEST_DATA` / `REQUEST_VARIABLE` + expression)

Catch and throw **must** share the same signal definition.

## Conditional catch

FormalExpression on `conditionalEventDefinition`. Empty condition never fires.

## Pitfalls

1. Wrong ISO8601 (`PT1H` vs `R/PT1H`)
2. Message/signal ref mismatch between throw and catch
3. Ignoring whitelist / allowed users on message catch (security)
4. Event-based gateway targets must be catch events from this family
