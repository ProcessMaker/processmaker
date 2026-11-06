# Skill: Process creation rules

Load before creating or updating a ProcessMaker process (BPMN). Companion node skills cover each element in detail — load only the nodes you are adding or changing.

## What a Process is

A ProcessMaker **Process** is a Laravel model whose executable definition is a BPMN 2.0 XML string in `processes.bpmn`, with ProcessMaker extensions in the `pm:` namespace (`http://processmaker.com/BPMN/2.0/Schema.xsd`).

Create API: `POST /api/1.0/processes` (`ProcessController::store`).

| Field | Required | Notes |
|-------|----------|-------|
| `name` | yes | Unique, `alpha_spaces` |
| `description` | yes | |
| `status` | no | `ACTIVE` \| `INACTIVE` \| `ARCHIVED` |
| `process_category_id` | no | Must exist |
| `bpmn` | no | Full BPMN XML; if omitted, template `OnlyStartElement.bpmn` (empty executable process — **add a start event before running**) |

Also accepts uploaded `file` (BPMN). Schema is validated; HTML entities in BPMN are normalized.

Update BPMN: `PUT /api/1.0/processes/{id}` or `PUT .../bpmn`. Prefer package-plg `POST /api/1.0/package-plg/deploy-process` when publishing from git with portable refs.

## BPMN skeleton (minimum runnable)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn" exporter="ProcessMaker Modeler">
  <bpmn:process id="ProcessId" name="my-process" isExecutable="true">
    <bpmn:startEvent id="node_1" name="Start"
      pm:allowInterstitial="true"
      pm:interstitialScreenRef="interstitial">
      <bpmn:outgoing>node_4</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="node_2" name="Form Task" pm:screenRef="${screen:my-form}" pm:assignment="requester">
      <bpmn:incoming>node_4</bpmn:incoming>
      <bpmn:outgoing>node_5</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_3" name="End">
      <bpmn:incoming>node_5</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="node_4" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="node_5" sourceRef="node_2" targetRef="node_3" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="ProcessId"><!-- shapes + edges --></bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
```

Rules:

1. Exactly **one** `bpmndi:BPMNDiagram` (API validates this).
2. Form Task element type is **`bpmn:Task`**, not `bpmn:UserTask`.
3. Node `id` must match Advanced inspector: `^[a-zA-Z][^\s][a-zA-Z0-9_-]+$` (e.g. `node_1`).
4. Every flow needs matching diagram edge; every shape needs bounds.
5. **Every sequence flow must also appear as `<bpmn:outgoing>` on the source and `<bpmn:incoming>` on the target.** Nayra wires the runtime graph from those child refs — `sourceRef`/`targetRef` alone is not enough. Without them, a Start Event has 0 outgoing flows and the Request never reaches the first task.
6. Do **not** put Form Task assignment values like `pm:assignment="requester"` on a Start Event (start permissions use `user` / `group` / `process_manager` or omit).
7. Interstitial (“Display Next Assigned Task to Task Assignee”): if you set `pm:allowInterstitial="true"` you **must** also set `pm:interstitialScreenRef` to a **DISPLAY** screen (system key `interstitial` is fine). Enabling interstitial without a screen leaves the UI stuck on loading after start. If you do not need that UX, omit both attributes.

## Portable references (git / deploy)

Prefer named refs over numeric IDs in package BPMN:

| Attr / field | Portable form |
|--------------|---------------|
| `pm:screenRef` | `${screen:exact-screen-title}` |
| `pm:scriptRef` | `${script:exact-script-title}` |
| `calledElement` (CallActivity) | `${process:exact-process-name}` |
| `web_entry.screen_id` / `completed_screen_id` | `${screen:…}` (supported by PLG deploy) |

Deploy resolves names → instance IDs. Leave other `pm:config` values as Modeler dumps them. Do **not** invent `${…}` inside arbitrary JSON keys.

Publish order: scripts → screens → child processes (CallActivity targets) → parent process.

## Data plane (request data)

Request data is a mutable associative array for the instance:

| Node | In | Out |
|------|----|-----|
| Form Task | request → FORM screen | submit merges into request |
| Manual Task | request → DISPLAY | ack; little field merge |
| Script Task | request + `$config` | return **array** merges into request |
| Service Task | request + `config` | implementation output merges |
| Call Activity | mapped subset → child | mapped child → parent |
| Gateway flows | FEEL/expression on request | optional `pm:config.update_data` |

## Inspector vs XML

Modeler UI writes:

- Top-level `pm:*` attributes on the element
- Nested BPMN (`eventDefinitions`, `conditionExpression`, `timerEventDefinition`, …)
- **`pm:config`**: stringified JSON for Web Entry, email notifications, connector settings, self-service, etc.

Never put connector settings in the wrong place: Service Tasks use BPMN `implementation` + JSON `config` / `pm:config`.

## Workflow

1. Discover — list processes; avoid duplicate `name`.
2. Ensure assets — upsert every `${script:}` / `${screen:}` / child `${process:}` first.
3. Author BPMN — start → tasks/gateways → end; load node skills for each element you configure.
4. Validate — see skill `process-validation` (schema, refs, gateway defaults, screen types).
5. Create/update — API or PLG `deploy-process`.
6. Verify — get BPMN; confirm refs, one diagram, runnable path from start to end.

## Do not

- Invent `bpmn:UserTask` for Form Tasks
- Ship numeric `pm:screenRef` / `pm:scriptRef` into git when portable refs are available
- Leave blank create without a start event
- Put gateway conditions on the gateway node (they belong on **sequence flows**)
- Dump unrelated package `implementation` keys without the matching connector installed
