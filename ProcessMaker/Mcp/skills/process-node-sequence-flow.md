# Skill: Sequence Flow (`bpmn:SequenceFlow`)

Use when connecting nodes or configuring gateway conditions, flow variables, or stages.

## Identity

| | |
|--|--|
| Modeler id | `processmaker-modeler-sequence-flow` |
| BPMN type | `bpmn:SequenceFlow` |

Also: Message Flow (`bpmn:MessageFlow`) for pool-to-pool; Association for text annotations — not sequence.

## Core attributes

| Field | Location | Purpose |
|-------|----------|---------|
| id | `id` | e.g. `node_4` |
| source / target | `sourceRef`, `targetRef` | Node ids |
| name | `name` | Optional label on the edge |
| Condition | `conditionExpression` FormalExpression `body` | Only when source is Exclusive or Inclusive |
| Flow variable | `pm:config.update_data` | Set variable when flow taken (XOR) |
| Stages | StageManager → config | Process stage markers |

## Condition expression

Injected in the inspector when `sourceRef` is Exclusive or Inclusive:

```xml
<bpmn:sequenceFlow id="node_51" name="Yes" sourceRef="node_50" targetRef="node_60">
  <bpmn:conditionExpression xsi:type="bpmn:tFormalExpression">
    status=="Approved" &amp;&amp; score &gt;= 70
  </bpmn:conditionExpression>
</bpmn:sequenceFlow>
```

Mark the else path on the gateway: `default="node_52"` (the flow id), often with an empty or absent condition.

## Flow variable update (`pm:config`)

When the flow is selected (Exclusive), optional:

```json
{ "update_data": { "variable": "routeTaken", "expression": "\"A\"" } }
```

## Data

- **In (condition):** reads request data
- **Out:** optional `update_data` writes into request; token moves to `targetRef`

## Pitfalls

1. Conditions on Parallel gateway outflows are wrong
2. HTML/XML escaping of `&&`, `<`, `>`
3. Missing `default` on XOR with incomplete conditions → stuck
4. Empty FormalExpression sometimes used as visual “default” — prefer gateway `default=`
5. Diagram must include a `BPMNEdge` for every sequence flow
6. **Must** mirror each flow as `<bpmn:outgoing>{flowId}</bpmn:outgoing>` on `sourceRef` and `<bpmn:incoming>{flowId}</bpmn:incoming>` on `targetRef`. Without those children Nayra does not attach the flow (Start Event stays at 0 outgoing; first task never appears)
