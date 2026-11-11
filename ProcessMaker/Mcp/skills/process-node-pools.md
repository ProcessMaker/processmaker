# Skill: Pools and Lanes

Use when modeling multi-participant diagrams (pools, lanes, message flows between pools).

## Identity

| | Pool | Lane |
|--|--|--|
| Modeler id | `processmaker-modeler-pool` | `processmaker-modeler-lane` |
| BPMN type | `bpmn:Participant` | `bpmn:Lane` |
| Palette | yes | no (`control: false`; created from pool crown) |
| Config | none (`name` / `id` / documentation only) | same |

Pools live under a `bpmn:collaboration`. Each participant references a `bpmn:process` via `processRef`. Lanes go in that process `laneSet` with `flowNodeRef` children.

## Inspector fields

| Field | Attr | Required |
|-------|------|----------|
| Name | `name` | yes |
| Node id | `id` | yes (Advanced) |
| Documentation | documentation child | no |

No `pm:config` / `implementation`.

## Diagram rules

1. Collaboration + participants for multi-pool processes.
2. Executable work stays in the process that owns the flow nodes (`isExecutable` as needed).
3. Cross-pool links use `bpmn:messageFlow` (see also `process-node-sequence-flow`) — not `sequenceFlow`.
4. Place flow nodes inside the correct pool’s process; update lane `flowNodeRef` when using lanes.
5. Include BPMN DI (`BPMNShape` / `BPMNEdge`) for participants, lanes, and flows.

## Minimal BPMN sketch

```xml
<bpmn:collaboration id="collaboration_0">
  <bpmn:participant id="node_pool_a" name="Applicant" processRef="Process_A" />
  <bpmn:participant id="node_pool_b" name="Back Office" processRef="Process_B" />
  <bpmn:messageFlow id="mf_1" sourceRef="node_task_a" targetRef="node_task_b" />
</bpmn:collaboration>
<bpmn:process id="Process_A" isExecutable="true">
  <bpmn:laneSet>
    <bpmn:lane id="node_lane_a" name="Submit">
      <bpmn:flowNodeRef>node_task_a</bpmn:flowNodeRef>
    </bpmn:lane>
  </bpmn:laneSet>
  <!-- start / tasks / end for Process_A -->
</bpmn:process>
<bpmn:process id="Process_B" isExecutable="false">
  <!-- counterpart process -->
</bpmn:process>
```

## Pitfalls

1. Treating a pool like a Task — it is a Participant + processRef.
2. Using sequenceFlow across pools instead of messageFlow.
3. Orphan flow nodes not listed in any lane when lanes are used.
4. Missing DI bounds for large pool shapes (default ~600×300).
