# Skill: Gateway nodes

Use when adding Exclusive, Inclusive, Parallel, or Event-based gateways.

## Identity

| Modeler id | BPMN | Split behavior |
|------------|------|----------------|
| `processmaker-modeler-exclusive-gateway` | `bpmn:ExclusiveGateway` | Exactly one outgoing path |
| `processmaker-modeler-inclusive-gateway` | `bpmn:InclusiveGateway` | All matching condition paths (OR) |
| `processmaker-modeler-parallel-gateway` | `bpmn:ParallelGateway` | All paths (AND); no conditions |
| `processmaker-modeler-event-based-gateway` | `bpmn:EventBasedGateway` | First succeeding catch event |

Gateway inspector is mostly Name + Advanced id. **Conditions live on Sequence Flows**, not on the gateway. See skill `process-node-sequence-flow`.

## Exclusive (XOR)

```xml
<bpmn:exclusiveGateway id="node_50" name="Approved?" default="node_52" />
```

- Evaluate outgoing flow conditions against request data
- Use `default="flowId"` for the else path
- Without default and without a matching condition → **stuck token**

## Inclusive (OR)

- All flows whose conditions are true are taken
- Converging inclusive join waits for active paths
- Optional `gatewayDirection` Diverging|Converging
- Pitfall: join deadlocks if path accounting is wrong

## Parallel (AND)

- Fork: all outgoing; Join: wait for all incoming
- **Do not** put `conditionExpression` on parallel outflows
- Unbalanced fork/join pairs cause hangs

## Event-based

- Outgoing must target intermediate **catch** events only
- Token waits until the first catch succeeds

## Data

Gateways do not transform data. Flow conditions **read** request data; optional flow `pm:config.update_data` **writes** when a flow is taken.

## Checklist

- [ ] XOR/OR: every meaningful branch has a condition or is marked `default`
- [ ] Parallel: no conditions; matched join
- [ ] Event-based: only catch-event targets
- [ ] Expressions XML-escape `&` as `&amp;`
