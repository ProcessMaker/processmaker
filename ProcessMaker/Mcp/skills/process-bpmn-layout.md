# Skill: BPMN diagram layout

Load when authoring BPMN for MCP and the diagram needs readable Modeler positions (DI), or when calling `layout-bpmn`.

## What it does

ProcessMaker runs **[bpmn-auto-layout-extended](https://github.com/krasucki/bpmn-auto-layout-extended)** on the server (`tools/mcp/bpmn-auto-layout`). It regenerates diagram interchange (shapes, edges, **lanes**, collaborations) while preserving BPMN semantics (`incoming`/`outgoing`, `pm:*`, conditions).

Compared to a flat graph layout only, this engine understands **swimlanes (`laneSet`)**, message flows, groups, and annotations.

## Agent workflow

1. Author BPMN structure and ProcessMaker attrs (`process-creation-rules` + node skills).
2. You may use placeholder DI (overlapping bounds are fine).
3. Call **`layout-bpmn`** to preview layout, or rely on **`auto_layout_bpmn`** (default **true**) on **`create-process`** / **`update-process`**.
4. Call **`validate-process`**, then create/update.

Do **not** spend tokens hand-tuning `dc:Bounds` or `di:waypoint` values.

## Tools

| Tool | Behavior |
|------|----------|
| `layout-bpmn` | Returns `{ bpmn, warnings, layout_engine: "bpmn-auto-layout-extended" }` without saving |
| `create-process` / `update-process` | Layout before save when `auto_layout_bpmn` is true **or** any `sequenceFlow` lacks `BPMNEdge` waypoints (forced). `validate-process` errors with `missing_bpmn_edge` if layout is disabled. |

## Operations

- Requires **Node.js ≥ 18** and `npm ci` in `tools/mcp/bpmn-auto-layout` on the ProcessMaker host.
- Disable globally: `MCP_BPMN_LAYOUT_ENABLED=false` (or legacy `MCP_BPMN_AUTO_LAYOUT=false`).
- Optional script override: `MCP_BPMN_LAYOUT_SCRIPT` (defaults to `tools/mcp/bpmn-auto-layout/cli.mjs`).
- Preserve manual coordinates: `auto_layout_bpmn: false` only when every flow already has `BPMNEdge` + waypoints; otherwise MCP still auto-layouts.

## Limits

- Extremely large diagrams may hit timeout (`MCP_BPMN_LAYOUT_TIMEOUT`, default 30s).
- Sub-processes stay collapsed unless the layout library is called with `expandSubProcesses` (not exposed on MCP tools yet).
