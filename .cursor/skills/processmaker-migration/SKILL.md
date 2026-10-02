---
name: processmaker-migration
description: >-
  Orchestrates ProcessMaker MCP workflows for PM3→PM4 migration, greenfield
  process design, and PM4 process improvement. Use when the user mentions
  ProcessMaker migration, pm3-migration-reader, processmaker-agent, MCP tools,
  export_process_bundle, apply_migration_bundle, pro_uid, or migrating/designing
  BPMN processes in PM4.
---

# ProcessMaker MCP Agent

## Core rules

1. **Never write to PM3/PM4 directly.** Use MCP tools only (`pm3-migration-reader`, `processmaker-agent`).
2. **Ask before writes.** Confirm with the user before `apply_migration_bundle`, `apply_process_design`, `create_process`, `update_process_bpmn`, or any tool that persists data.
3. **Start with compatibility.** Call `get_mcp_capabilities` on PM4 when starting a session or after a PM4 upgrade. If `platform_drift_detected` is true, report `platform_drift[]` before proceeding.
4. **Summarize outcomes.** Always return `process_id`, `validation`, `gap_report`, and a short manual checklist after migration or design.

Technical reference (architecture, file paths, tests): [reference.md](reference.md) and repo root `MCPReadme.md`.

## MCP servers

| Server | Namespace | Role |
|--------|-----------|------|
| `pm3-migration-reader` | PM3 | Export bundles, inventory, point lookups |
| `processmaker-agent` | PM4 | Import, design, analyze, fix (`migration-writer` is alias) |

Requires Cursor **Agent** mode and both servers green in Settings → MCP.

## Mode selection

| User goal | PM3 tools | PM4 tools |
|-----------|-----------|-----------|
| Migrate PM3→PM4 | Yes | Yes |
| Create new process | No | Yes |
| Improve existing PM4 | No | Yes |

## Mode 1 — PM3→PM4 migration

### Tool sequence

```
list_processes (PM3)
  → get_migration_inventory (PM3)
  → export_process_bundle (PM3)
  → apply_migration_bundle (PM4)   # user must confirm
  → validate_process (PM4)         # included in finalize; report anyway
```

### Agent behavior

- Resolve process by name with `list_processes` + `filter_name`; confirm `pro_uid` with user.
- Present inventory risks from `migration_risks` before export.
- **Subprocesses:** migrate child processes first; pass `subprocess_map` with child PM4 `process_id`.
- **ABE:** if bundle has `abe_configurations`, ask for `email_server_map` (PM3 SMTP UID → PM4 email server ID) when not inferable.
- **Do not** claim users or effective task assignments were migrated — they are manual in PM4.
- One `pro_uid` per migration run; validate before starting the next process.

### Resume / retry

| Situation | Action |
|-----------|--------|
| Import interrupted (`in_progress` / `failed` log) | `get_migration_log` → `resume_migration_bundle` or `apply_migration_bundle` with `resume: true` |
| Error "Use resume=true" | Same as above |
| Log already `completed`, user wants another attempt | `apply_migration_bundle` with `fresh: true` (new PM4 process; old artifacts may remain) |
| Error "Use fresh=true" | Use `fresh: true` |

### Final report template

```
process_id: <id>
validation.valid: <bool>
gap_report.ready: <bool> (score: <n>)
linked_screens / scripts: <summary>
Manual steps:
1. Open process in PM4 designer — verify diagram canvas
2. Configure task assignments (users/groups)
3. Run New Request test case
4. Address gap_report.gaps[] if ready is false
```

## Mode 2 — Greenfield design

Prefer **`apply_process_design`** with a single plan when the user describes a full flow.

Alternative step-by-step: `create_process` → `create_screen_from_fields` → `link_screen_to_task` → `validate_process`.

Always confirm before the first write. End with `validate_process` + `analyze_process`.

## Mode 3 — Process improvement

```
analyze_process → present findings → user confirms fixes
  → link_screen_to_task | create_screen_from_fields | add_bpmn_* | update_process_bpmn
  → analyze_process again
```

Do not apply fixes silently when severity is `error` or `warning`.

## Key terms

| Term | Meaning |
|------|---------|
| `pro_uid` | PM3 process UUID; migration log key |
| `process_id` | PM4 integer ID |
| `bundle` | Output of `export_process_bundle` |
| `task_element_map` | `tas_uid` → BPMN `node_X`; drives screen links |
| `gap_report.ready` | `true` = no critical gaps detected |

## Common errors (agent response)

| Symptom | Fix |
|---------|-----|
| Screen not linked in gap_report | `link_screen_to_task` with `element_id` from gap or inventory |
| Subprocess missing callActivity | Migrate child first; add `subprocess_map` |
| Empty BPMN canvas | Re-export from PM3; ensure bundle has `bpmndi` |
| PM4 tools incomplete in Cursor | Clear `~/.cursor/projects/<workspace>/mcps/user-processmaker-agent/` and restart MCP |
| `bpmnElement` XSD error | Restart PM4 MCP server (stale code) |

## What MCP does not do

- Start cases / New Request execution
- Sync PM3 users or groups
- Auto-layout when **adding** new BPMN nodes in PM4 (migration preserves PM3 canvas)
- Full UI parity with ProcessMaker designer

For tool parameters and JSON examples, see `MCPReadme.md` § Security (dev-only) before production use.
