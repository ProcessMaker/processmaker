# Skill: Start Event nodes

Use when adding or configuring any Start Event (blank, timer, message, signal, conditional) or start permissions / interstitial.

## Modeler ↔ BPMN

All variants are `bpmn:StartEvent`. Subtype = `eventDefinitions[0].$type`.

| Modeler id | Event definition | Trigger |
|------------|------------------|---------|
| `processmaker-modeler-start-event` | none | Manual / in-app / Web Entry |
| `processmaker-modeler-start-timer-event` | `TimerEventDefinition` (`timeCycle`) | Scheduler |
| `processmaker-modeler-message-start-event` | `MessageEventDefinition` | Message |
| `processmaker-modeler-signal-start-event` | `SignalEventDefinition` | Signal |
| `processmaker-modeler-conditional-start-event` | `ConditionalEventDefinition` | Condition |

Incoming sequence flows are invalid (`validateIncoming → false`).

## Blank start (most common)

**Default XML:** `<bpmn:startEvent id="node_1" name="Start Event" />`

### Inspectors (core)

| UI | Attrs | Purpose |
|----|-------|---------|
| Name | `name` | Label |
| Start Permissions | `pm:assignment`, `pm:assignedUsers`, `pm:assignedGroups` | Who may start a Request |
| Interstitial | `pm:allowInterstitial`, `pm:interstitialScreenRef` | After start, send assignee to next task |
| Web Entry | `pm:config.web_entry` | Public/authenticated URL — see skill `process-web-entry` |

### Interstitial (Display Next Assigned Task to Task Assignee)

Modeler checkbox: **Display Next Assigned Task to Task Assignee** → `pm:allowInterstitial`.

| `allowInterstitial` | Required attrs | Notes |
|---------------------|----------------|-------|
| omit / `false` | none | Starter is **not** auto-redirected into the next task UI |
| `true` | **`pm:interstitialScreenRef` must be set** | Screen type **DISPLAY** only (Modeler Screen Interstitial is `required`) |

Rules:

1. Never set `pm:allowInterstitial="true"` without a non-empty `pm:interstitialScreenRef`. Missing ref leaves the UI stuck on loading and the next task never appears.
2. Prefer the system interstitial: `pm:interstitialScreenRef="interstitial"` (screen key `interstitial`, title “Screen Interstitial”), or a portable `${screen:Screen Interstitial}` / numeric id of a **DISPLAY** screen.
3. If you do not want interstitial UX, omit both attrs (or set `allowInterstitial="false"`) — do not enable the flag “for convenience” without a screen.
4. Same pairing applies on Form Task / Manual Task when their Interstitial inspector is enabled.

Example (blank start with interstitial):

```xml
<bpmn:startEvent id="node_1" name="Start"
  pm:allowInterstitial="true"
  pm:interstitialScreenRef="interstitial">
  <bpmn:outgoing>node_4</bpmn:outgoing>
</bpmn:startEvent>
```

### Data

- **In:** empty request (plus Web Entry form / query params when enabled)
- **Out:** creates the Request; token moves to the outgoing flow

### Pitfalls

- API create without BPMN yields template **without** a usable start — always add one
- Permissions empty ≠ “everyone”; configure users/groups intentionally
- Web Entry ANONYMOUS overrides assignment to the anonymous user
- `allowInterstitial="true"` without `interstitialScreenRef` → infinite loading after start

## Timer start

```xml
<bpmn:startEvent id="node_1" name="Timer Start">
  <bpmn:timerEventDefinition>
    <bpmn:timeCycle xsi:type="bpmn:tFormalExpression">R/PT1H</bpmn:timeCycle>
  </bpmn:timerEventDefinition>
</bpmn:startEvent>
```

Inspector: Timing Control → ISO8601 / cycle body. Empty cycle = never fires. Timezone/cron semantics are engine-specific.

## Message start

- Select `messageRef` in definitions
- Payload lands in request (message variable)
- Throw/end message must use the **same** message definition

## Signal start

- Select signal + Request Variable (`pm:config` / signal config) for payload storage
- Throw side configures payload type (`NONE` / `ALL_REQUEST_DATA` / `REQUEST_VARIABLE`)

## Conditional start

```xml
<bpmn:conditionalEventDefinition>
  <bpmn:condition xsi:type="bpmn:tFormalExpression">ready == true</bpmn:condition>
</bpmn:conditionalEventDefinition>
```

Empty condition never starts.

## Checklist

- [ ] Exactly one primary start path for the intended trigger
- [ ] Permissions set for blank starts that are not Web Entry–anonymous
- [ ] Timer/message/signal/condition definitions non-empty and consistent with throwers
- [ ] Web Entry (if any) validated via `process-web-entry`
- [ ] If `allowInterstitial` is true → `interstitialScreenRef` set to a DISPLAY screen (e.g. `interstitial`)
- [ ] Start Event has `<bpmn:outgoing>` for every outgoing sequence flow
