# ProcessMaker MCP — Reference Guide

Agents (Cursor, FlowGenie, etc.) can **migrate PM3→PM4**, **create new processes**, and **analyze/improve** existing ones using MCP servers.

You do not need PHP or BPMN to get started: write a prompt and the agent invokes tools for you. The **LLM does not write to the database**; all persistence goes through `Reader.php` (PM3) or `Writer.php` / `Designer.php` (PM4).

---

## Table of Contents

1. [Glossary](#glossary)
2. [Three usage modes](#three-usage-modes)
3. [Cursor setup](#cursor-setup)
4. [First test](#first-test)
5. [Post-upgrade compatibility](#post-upgrade-compatibility)
6. [Mode 1 — PM3→PM4 migration](#mode-1--pm3pm4-migration)
7. [Bundle and import steps](#bundle-and-import-steps)
8. [Log, resume, and fresh](#log-resume-and-fresh)
9. [Detailed examples](#detailed-examples)
10. [Mode 2 — Greenfield design](#mode-2--greenfield-design)
11. [Mode 3 — Process improvement](#mode-3--process-improvement)
12. [MCP tools](#mcp-tools)
13. [What migrates / what does not](#what-migrates--what-does-not)
14. [Post-import checklist](#post-import-checklist)
15. [Common errors](#common-errors)
16. [Debug and architecture](#debug-and-architecture)
17. [Repository files](#repository-files)
18. [Tests](#tests)
19. [Limitations](#limitations)

---

## Glossary

| Term | Description |
|------|-------------|
| **Process** | Workflow (tasks, decisions, forms). |
| **PM3 / PM4** | ProcessMaker version 3 (source) and 4 (target). |
| **pro_uid** | Process UUID in PM3. Key for export and migration log. |
| **process_id** | Integer process ID in PM4 (e.g. `87`). Shown in Processes UI. |
| **bundle** | JSON from `export_process_bundle`; input to `apply_migration_bundle`. |
| **workspace** | PM3 instance (default `workflow`). |
| **task_element_map** | Map `tas_uid` PM3 → BPMN node (`node_X`). Required to link screens. |
| **pm3_uid / pm4_id** | Mapping trigger/dynaform PM3 → script/screen PM4. |
| **gap_report** | Post-import gaps; `ready: true` = nothing critical detected. |
| **migration_log** | JSON at `storage/app/migration-logs/{pro_uid}.json`; enables resume. |
| **Screen** | Form shown in a PM4 task. |
| **Script** | Automated PHP in PM4. |
| **BPMN** | Diagram XML (start, tasks, end + `bpmndi` for canvas). |
| **MCP tool** | Function invoked by the agent (e.g. `list_processes`). |

---

## Three usage modes

| Mode | PM3 server | PM4 server | Goal |
|------|------------|------------|------|
| Migration | `pm3-migration-reader` | `processmaker-agent` | Copy PM3 process to PM4 |
| Design | — | `processmaker-agent` | Create process from scratch |
| Improve | — | `processmaker-agent` | Analyze and fix PM4 process |

`migration-writer` is an **alias** of `processmaker-agent` (same **34 tools**).

### Common flow

```
User → Cursor Agent → MCP tool (JSON) → PM core (DB)
     ← summary + process_id ← structured JSON ←
```

**Migration:** `list_processes` → `get_migration_inventory` → `export_process_bundle` → `apply_migration_bundle` → `validate_process`

**Design:** `apply_process_design` or individual tools

**Improve:** `analyze_process` → fix with tools → verification `analyze_process`

### Classic vs BPMN in PM3

| `project_type` | Export behavior |
|----------------|-----------------|
| `classic` | BPMN built from routes/gateways + `TAS_POS*` positions. |
| `bpmn` | Native PM3 BPMN + `BPMN_BOUND` coordinates and `FLO_STATE` waypoints. |

The MCP flow is the same: export → apply. The difference is internal in `BpmnXmlExporter`.

### Order when migrating multiple processes

1. **Child subprocesses first**, then parent (`subprocess_map` needs the child’s PM4 `process_id`).
2. **One `pro_uid` at a time** — each has its own log.
3. Validate in PM4 before migrating the next one.

---

## Cursor setup

**Requirements:** PM3 and PM4 running (local or Docker). Chat mode **Agent** (not Ask).

Register **two servers** in `.cursor/mcp.json` (adjust paths):

```json
{
  "mcpServers": {
    "pm3-migration-reader": {
      "command": "/Users/user/srv/http/processmaker3/workflow/engine/bin/mcp-migration-reader.sh",
      "args": ["workflow"],
      "env": { "PM_WORKSPACE": "workflow" }
    },
    "processmaker-agent": {
      "command": "/opt/homebrew/bin/php",
      "args": [
        "/Users/user/srv/http/processmaker4/artisan",
        "mcp:start",
        "processmaker-agent"
      ],
      "cwd": "/Users/user/srv/http/processmaker4"
    }
  }
}
```

**PM3:** `mcp-migration-reader.sh` → `mcp-migration-reader.php` (stdio JSON-RPC, no HTTP).

**PM4:** `php artisan mcp:start processmaker-agent` (stdio). Optional HTTP: `/mcp/processmaker-agent`.

### PM3 environment variables (optional)

| Variable | When |
|----------|------|
| `PM_WORKSPACE` | PM3 workspace (default `workflow`) |
| `PM3_PATH_DATA` | When `paths_installed.php` points to a missing host path |
| `PM3_MCP_DB_HOST` | When `db.php` uses Docker hostname (`db`) |
| `PM3_MCP_DB_PORT` | DB port on host (default `3307`) |
| `PM3_PHP` | PHP binary (default `php`) |

### Enable

1. Save `mcp.json`.
2. Toggle MCP off/on in Cursor. If tools are missing: delete `~/.cursor/projects/<workspace>/mcps/user-processmaker-agent/` and restart.
3. Confirm both servers are green in Settings → MCP.

---

## First test

```
Run get_mcp_capabilities and tell me mcp_version and whether platform_drift_detected is false.
Then list the first 5 PM4 processes with list_processes.
```

Expected: version `1.2.0`, drift `false`, list with `id` and `name`.

PM3 test: `"list PM3 processes named test"` → `list_processes`.

---

## Post-upgrade compatibility

```
Run get_mcp_capabilities and tell me if platform_drift_detected is true.
```

| Field | Meaning |
|-------|---------|
| `platform_drift_detected: false` | MCP compatible with current PM4 core |
| `platform_drift_detected: true` | PM4 changed; review `platform_drift[]` and update MCP |
| `supported_features` | Implemented capabilities |
| `not_supported_by_mcp` | Known limits |

CI test: `tests/unit/Mcp/McpSupportTest.php`

---

## Mode 1 — PM3→PM4 migration

### Prompt (copy and adapt)

```
Migrate PM3 process "Invoice Approval" to PM4.

Rules:
- Step by step; ask "Shall we continue?" before each important action.
- PM3: list_processes → get_migration_inventory → export_process_bundle
- PM4: apply_migration_bundle (only with my explicit OK)
- If interrupted: get_migration_log + resume_migration_bundle
- Do not migrate users or effective task assignments.
- If subprocesses exist, migrate the child first or pass subprocess_map.
- If ABE exists, ask for email_server_map if you cannot infer it.
- At the end: PM4 process_id, gap_report, warnings, and manual checklist.
```

### Typical conversation

```
You:  Migrate PM3 process "Invoice Approval" to PM4.

AI:   Found 1 process: PRO_UID f4b2c8e0-..., title "Invoice Approval". Continue?

You:  Yes.

AI:   Inventory: 4 tasks, 2 dynaforms, 3 triggers. Risk: PM3 assignments (manual in PM4).
      Export the bundle?

You:  Yes.

AI:   Bundle ready (BPMN + diagram + screens + scripts). Import to PM4?

You:  Yes, import.

AI:   Done: process_id 87, validation.valid true, gap_report.ready true.
      Review assignments in Processes → 87.
```

### Conversational flow

| Step | Tool | Your action |
|------|------|-------------|
| 1 | `list_processes` | Confirm `pro_uid` |
| 2 | `get_migration_inventory` | Continue or cancel based on risks |
| 3 | `export_process_bundle` | OK to import |
| 4 | `apply_migration_bundle` | Review `process_id`, `validation`, `gap_report` |
| 5 | (optional) gaps | Fix with PM4 tools or manually |
| 6 | PM4 browser | Post-import checklist |

---

## Bundle and import steps

### Bundle sections

| Section | Content |
|---------|---------|
| `source` | `pro_uid`, `title`, `project_type` |
| `bpmn.xml` | Diagram + `bpmndi:BPMNDiagram` (PM3 layout) |
| `tasks`, `steps`, `task_element_map` | Task ↔ screen/script ↔ BPMN node links |
| `triggers` | `TRI_WEBBOT` with `@@var` syntax |
| `dynaforms`, `stylesheets` | Forms and CSS |
| `variables`, `task_assignments` | Metadata (effective assignment is manual) |
| `timer_events`, `sub_processes`, `web_entries` | Timers, subprocesses, web entry |
| `report_tables`, `abe_configurations` | Collections, ABE |
| `migration_risks` | Inventory warnings |

**Key relationship:** `steps` (tas_uid + dyn_uid) + `task_element_map` (tas_uid → node_X) → `link_assets` writes `pm:screenRef` on the BPMN node.

### Optional bundle fields (PM4)

```json
"subprocess_map": { "CHILD-UID-PM3": "42" },
"subprocess_start_event_map": { "CHILD-UID-PM3": "node_2" },
"email_server_map": { "SMTP-UID-PM3": "1" }
```

- **subprocess_map:** Numeric ID of the already-migrated child process in PM4.
- **email_server_map:** Email Server ID in PM4 (Settings → Email Servers).

### `runMigrationBundle` order (13 steps)

| Log step | Action |
|----------|--------|
| `create_process` | PM4 process + sanitized BPMN |
| `create_scripts` | Translated scripts (`TriggerTranslator`) |
| `create_screens` | Screens from dynaforms (`DynaformConverter`) |
| `link_assets` | `screenRef` / `scriptRef` on BPMN |
| `apply_assignments` | Assignment metadata (no PM4 users) |
| `apply_variables` | Variables in `process.properties` |
| `bpmn_enhance` | Boundary timers, callActivity |
| `store_metadata` | document_steps, timers, subprocesses |
| `web_entry` | Start event config |
| `step_triggers` | Before/after triggers on BPMN |
| `collections` | Report tables → Collection |
| `abe` | Actions By Email |
| `finalize` | `validate_process` + `gap_report` |

On disk: `storage/app/migration-logs/{pro_uid}.json`

---

## Log, resume, and fresh

### Log structure

- `status`: `in_progress` | `failed` | `completed`
- `process_id`, `completed_steps`, `pending_steps`
- `artifacts`: scripts/screens/collections with `pm3_uid` → `pm4_id`

### Resume interrupted import

```
1. get_migration_log({ "pro_uid": "..." })
2. resume_migration_bundle({ "bundle": { ...same export... } })
   — or —
   apply_migration_bundle({ "bundle": {...}, "resume": true })
```

- Reuses `process_id` and artifacts already created (by `pm3_uid` in log).
- Bundle can be a fresh re-export if `pro_uid` matches.

### `fresh: true`

- Deletes **only** the log JSON.
- Creates a **new** process in PM4 (new `process_id`).
- **Does not delete** processes/scripts/screens from the previous attempt → manual cleanup if needed.

### Rules

| Situation | Action |
|-----------|--------|
| Log `in_progress` / `failed` | `resume: true` (not normal apply) |
| Log `completed` | `fresh: true` to retry with a new log |
| Error "Use resume=true" | Partial log → resume |
| Error "Use fresh=true" | `pro_uid` already completed → fresh for another attempt |

---

## Detailed examples

### A — Export and import (minimal requests)

**PM3 — list_processes**
```json
{ "filter_name": "Invoice Approval", "start": 0, "limit": 10 }
```

**PM3 — export_process_bundle**
```json
{ "pro_uid": "f4b2c8e0-1a3d-4f5e-9b6c-7d8e9f0a1b2c" }
```

**PM4 — apply_migration_bundle**
```json
{ "bundle": { "...full export object..." }, "fresh": true }
```

### B — Typical import response

```json
{
  "process_id": 87,
  "scripts": [{ "pm3_uid": "c3d4...", "pm4_id": 15, "title": "Set Approved" }],
  "screens": [{ "pm3_uid": "d4e5...", "pm4_id": 22, "title": "Invoice Form" }],
  "linked_screens": [{
    "tas_uid": "task-review-uid",
    "element_id": "node_3",
    "screen_id": 22
  }],
  "validation": { "valid": true, "errors": [] },
  "gap_report": { "ready": true, "gaps": [], "score": 100 },
  "migration_log": { "status": "completed", "process_id": 87 }
}
```

### C — PM3 trigger → PM4 script

Input:
```php
<?php @@approved = "YES"; @@approver = @@USER_ID; ?>
```

Output (`TriggerTranslator`):
```php
<?php
$data['approved'] = "YES";
$data['approver'] = $data['USER_ID'];
return [];
```

### D — gap_report with pending items

```json
{
  "ready": false,
  "gaps": [
    "Dynaform step d4e5... is not linked to a BPMN task.",
    "Task assignments reference PM3 users; configure assignment manually in PM4."
  ],
  "score": 75
}
```

- First gap → check `task_element_map` or use `link_screen_to_task`.
- Second gap → expected; configure in PM4 designer.

---

## Mode 2 — Greenfield design

### Prompt

```
Create PM4 process "Invoice Approval" with:
- Fields invoice_number and amount on the request screen
- Review task with that screen
- Variable invoice_amount default 0

Use apply_process_design. Ask for confirmation before writing.
At the end: process_id, validate_process, analyze_process.
```

### Minimal plan

```json
{
  "plan": {
    "process": { "name": "Invoice Approval", "description": "Simple approval flow" },
    "bpmn_template": "SingleTask",
    "screens": [{
      "title": "Invoice Form",
      "fields": [
        { "variable": "invoice_number", "label": "Invoice #", "type": "text", "required": true },
        { "variable": "amount", "label": "Amount", "type": "number", "required": true }
      ]
    }],
    "variables": [{ "name": "invoice_amount", "type": "string", "default": "0" }]
  }
}
```

### Internal flow

```
ApplyProcessDesignTool → Designer.applyDesignPlan()
  → Writer.createProcess (SingleTask.bpmn)
  → ScreenBuilder → Writer.createScreen
  → Writer.linkScreenToTask (element_id UserTaskUID)
  → validateProcess + Analyzer.analyze
```

### Step-by-step alternative

```
create_process({ "name": "Leave Request" })
create_screen_from_fields({ "title": "Leave Form", "fields": [...], "process_id": "92", "element_id": "UserTaskUID" })
validate_process({ "process_id": "92" })
```

`UserTaskUID` = task id in `SingleTask.bpmn` template.

---

## Mode 3 — Process improvement

### Prompt

```
Analyze PM4 process ID 87 with analyze_process.
List findings and suggestions.
Propose changes and ask for OK before applying.
```

### Analysis example

```json
{
  "process_id": "87",
  "score": 75,
  "findings": [{
    "severity": "warning",
    "code": "task_without_screen",
    "message": "Task \"Review\" has no screen linked.",
    "element_id": "node_3"
  }],
  "suggestions": ["Link a screen with create_screen_from_fields or link_screen_to_task."]
}
```

### Fix

```
create_screen_from_fields({ "title": "Review Form", "fields": [...], "process_id": "87", "element_id": "node_3" })
analyze_process({ "process_id": "87" })
```

---

## MCP tools

### PM3 (`pm3-migration-reader`)

| Tool | Use |
|------|-----|
| `list_processes` | Search by name (`filter_name`, `start`, `limit`) |
| `get_migration_inventory` | Summary and risks before migration |
| `export_process_bundle` | Full export (`pro_uid` required) |
| `get_dynaform`, `get_trigger`, `get_task_steps`, `get_task_assignments` | Point lookup |
| `get_process_variables`, `get_report_tables`, `get_abe_configurations`, `get_stylesheets`, `get_workflow_metadata` | Point lookup |

### PM4 (`processmaker-agent`)

**Compatibility:** `get_mcp_capabilities`

**Migration:** `apply_migration_bundle`, `resume_migration_bundle`, `get_migration_log`, `list_migration_logs`, `get_migration_gap_report`, `create_screen_from_dynaform`, `link_migration_assets`, `apply_task_assignments`, `apply_abe_configuration`, `create_collection_from_report_table`

**Read:** `list_processes`, `get_process`, `get_process_inventory`, `get_process_bpmn`, `list_screens`, `get_screen`, `list_scripts`, `get_script`, `analyze_process`

**Design:** `apply_process_design`, `create_process`, `update_process`, `create_screen`, `create_screen_from_fields`, `create_script`, `add_bpmn_user_task`, `add_bpmn_sequence_flow`, `add_bpmn_exclusive_gateway`, `apply_process_variables`

**Fix:** `validate_process`, `update_process_bpmn`, `link_screen_to_task`, `link_script_to_task`

Full list: `ProcessMaker/Mcp/Migration/WriterServer.php`

---

## What migrates / what does not

| Automatic | Manual afterward |
|-----------|------------------|
| Process + BPMN + diagram (`bpmndi`) | Users and groups |
| Translated scripts | Effective task assignment |
| Dynaforms → screens (+ scoped CSS) | End-to-end business case test |
| Screen/script ↔ task links | Heavily custom PHP scripts |
| Process variables | Variables with external SQL |
| Timers, web entries, step triggers | |
| Report tables → collections | |
| ABE (with `email_server_map`) | |
| CallActivity (with `subprocess_map`) | |

---

## Post-import checklist

1. **Processes** → open by `process_id`.
2. **Diagram** → flow and visual canvas (start → tasks → end).
3. **Screens** / **Scripts** → IDs from import response.
4. **Task assignment** → PM4 users/groups.
5. **New Request** → test case (MCP does not start cases).
6. If `gap_report.ready: false` → address each item in `gaps[]`.

---

## Common errors

| Error / symptom | Cause | Fix |
|-----------------|-------|-----|
| "Use resume=true" | Partial log | `resume_migration_bundle` + same bundle |
| "Use fresh=true" | `pro_uid` already completed | `fresh: true` (creates new process) |
| Screen not linked (gap) | `task_element_map` / BPMN | `link_screen_to_task` |
| Subprocess without callActivity | Missing `subprocess_map` | Migrate child first |
| ABE not configured | Missing `email_server_map` | Add map to bundle |
| Empty BPMN canvas | Bundle without `bpmndi` | Re-export PM3 (current exporter includes diagram) |
| Incomplete PM4 MCP tools | Cursor cache | Clear MCP cache and restart |
| `bpmnElement` XSD error | Outdated PM4 MCP | Restart `processmaker-agent` after upgrade |

---

## Debug and architecture

```
Cursor Agent (LLM)
    |                    |
    v                    v
pm3-migration-reader   processmaker-agent (WriterServer v1.2.0)
Reader.php (PM3)       Writer / Reader / Designer / Analyzer (PM4)
    |                    |
    v                    v
PM3 DB                 PM4 DB + storage/app/migration-logs/
```

### PM3 — entry

```
mcp-migration-reader.sh workflow
  → mcp-migration-reader.php
  → ReaderServer::run() [STDIN JSON-RPC]
  → Reader.php / BpmnXmlExporter.php
  → Processes::getWorkflowData()
```

| Tool | Class |
|------|-------|
| `export_process_bundle` | `Reader::exportProcessBundle()` + `BpmnXmlExporter` |
| `list_processes` | `Reader::listProcesses()` |

### PM4 — entry

```
RouteServiceProvider → routes/ai.php → WriterServer
  stdio: php artisan mcp:start processmaker-agent
  HTTP:  /mcp/processmaker-agent
```

| Tool | Service |
|------|---------|
| `apply_migration_bundle` | `Writer::applyMigrationBundle()` |
| `apply_process_design` | `Designer::applyDesignPlan()` |
| `analyze_process` | `Analyzer::analyze()` |
| `get_mcp_capabilities` | `McpSupport::report()` |

### Main PM4 classes

| Class | Role |
|-------|------|
| `McpSupport` | Version, drift, acting user auth |
| `Writer` | Migration + MCP CRUD |
| `MigrationBpmn` | Sanitize IDs, validate BPMN |
| `BpmnDiagramInterchange` | Diagram fallback when `bpmndi` is missing |
| `MigrationLog` | Resume checkpoints |
| `MigrationGapReport` | Post-import comparator |
| `TriggerTranslator`, `DynaformConverter` | Scripts and screens |
| `BpmnEnhancer` | Timers, subprocesses |
| `Designer`, `Analyzer`, `ScreenBuilder`, `BpmnDesigner` | Design and improvement |

---

## Repository files

### PM3 (MCP runtime)

```
workflow/engine/bin/mcp-migration-reader.php   # entry point
workflow/engine/bin/mcp-migration-reader.sh    # Cursor launcher
workflow/engine/src/ProcessMaker/Mcp/Migration/
  ReaderServer.php
  Reader.php
  BpmnXmlExporter.php
```

Tests (not runtime): `tests/unit/ProcessMaker/Mcp/Migration/ReaderTest.php`, `BpmnXmlExporterTest.php`

### PM4

```
routes/ai.php
ProcessMaker/Mcp/Migration/Writer.php
ProcessMaker/Mcp/Migration/WriterServer.php
ProcessMaker/Mcp/Migration/MigrationBpmn.php
ProcessMaker/Mcp/Migration/BpmnDiagramInterchange.php
ProcessMaker/Mcp/Migration/Tools/*          # 17 migration tools
ProcessMaker/Mcp/Process/Tools/*            # 16 design/improvement tools
ProcessMaker/Mcp/McpSupport.php
```

---

## Tests

```bash
# PM4
./vendor/bin/phpunit tests/unit/Mcp/ tests/Feature/Mcp/Migration/WriterTest.php

# PM3 (requires configured test DB)
./vendor/bin/phpunit tests/unit/ProcessMaker/Mcp/Migration/
```

PM4: ~43 tests — migration, resume, agent, core compatibility (`McpSupportTest`).

---

## Limitations

- PM3 MCP does not start Docker or services; requires DB/`shared/` reachable from the host.
- Not full parity with PM4 UI.
- Does not start cases (`start request`).
- New gateways via MCP stay disconnected until `add_bpmn_sequence_flow`.
- When **adding** BPMN nodes in PM4, layout is not auto-adjusted (PM3→PM4 migration preserves canvas).
- Log is local disk; not shared across PM4 nodes.
- `fresh: true` may leave orphaned artifacts from the previous attempt.
- Resume requires the same `pro_uid` and a bundle consistent with the export.
