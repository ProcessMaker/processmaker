# Skill: Process BPMN validation

Use before create/update/deploy, or when implementing a future `validate-process` MCP tool. Validates structure, refs, and runtime-readiness — not visual layout aesthetics.

## Levels

1. **XML / schema** — well-formed BPMN; ProcessMaker schema validate on store/update
2. **Diagram** — exactly one `bpmndi:BPMNDiagram`; every element has shape/edge
3. **Graph** — reachable path start → end; no orphan nodes; boundary events attached
4. **PM semantics** — required `pm:*` refs, screen types, gateway defaults
5. **Assets** — referenced screens/scripts/processes exist (or portable titles resolve)

## Checklist

### Structure

- [ ] `xmlns:pm` present when using `pm:` attributes
- [ ] `bpmn:process` has `isExecutable="true"`
- [ ] One `BPMNDiagram` only
- [ ] Node ids match `^[a-zA-Z][^\s][a-zA-Z0-9_-]+$`
- [ ] Form Tasks are `bpmn:Task` (not `UserTask`)
- [ ] Every sequence flow is listed as `bpmn:outgoing` on its source and `bpmn:incoming` on its target (Nayra wiring)
- [ ] If `pm:allowInterstitial="true"` → non-empty `pm:interstitialScreenRef` (DISPLAY; key `interstitial` OK)

### Starts / ends

- [ ] At least one start event appropriate to the trigger
- [ ] Every token path can reach an end (or intentional terminate)
- [ ] Start permissions / Web Entry consistent with how the process is launched

### Tasks

- [ ] Form Task: `pm:screenRef` set; screen type FORM/CONVERSATIONAL
- [ ] Manual Task: `pm:screenRef` set; screen type DISPLAY
- [ ] Script Task: `pm:scriptRef` set; config JSON valid; script returns array
- [ ] Call Activity: `calledElement` non-empty; child published first
- [ ] Service Task: `implementation` known; config JSON valid

### Gateways / flows

- [ ] XOR/OR outflows have conditions or gateway `default`
- [ ] Parallel outflows have **no** conditions; joins balanced
- [ ] Event-based outflows target catch events only
- [ ] Condition expressions XML-escaped

### Web Entry / packages

- [ ] `web_entry: null` vs object intentional
- [ ] completed SCREEN/URL fields complete
- [ ] Connector packages installed for each `implementation`

### Portable git BPMN (PLG)

- [ ] `${screen:}` / `${script:}` / `${process:}` used instead of hard-coded ids where supported
- [ ] Filename basename == Process `name`
- [ ] No unresolved `${…}` titles (deploy → 422)

## Suggested validate-process tool output (future)

Return machine-readable issues:

```json
{
  "ok": false,
  "errors": [
    { "nodeId": "node_10", "code": "missing_screen_ref", "message": "Form Task requires pm:screenRef" }
  ],
  "warnings": [
    { "nodeId": "node_50", "code": "xor_no_default", "message": "Exclusive gateway has no default flow" }
  ]
}
```

Do not auto-fix BPMN unless the user asked; report and stop on errors that would 422 at deploy.
