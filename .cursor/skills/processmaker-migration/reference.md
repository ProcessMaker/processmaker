# ProcessMaker MCP — Agent reference

Full documentation: [`MCPReadme.md`](../../../MCPReadme.md) (repo root).

## Tool index

### PM3 (`pm3-migration-reader`)

| Tool | Purpose |
|------|---------|
| `list_processes` | Find processes (`filter_name`, `start`, `limit`) |
| `get_migration_inventory` | Pre-migration summary + risks |
| `export_process_bundle` | Full bundle for `apply_migration_bundle` |
| `get_dynaform`, `get_trigger`, `get_task_steps`, `get_task_assignments` | Point lookups |
| `get_process_variables`, `get_report_tables`, `get_abe_configurations`, `get_stylesheets`, `get_workflow_metadata` | Point lookups |

### PM4 (`processmaker-agent`)

| Category | Tools |
|----------|-------|
| Compatibility | `get_mcp_capabilities` |
| Migration | `apply_migration_bundle`, `resume_migration_bundle`, `get_migration_log`, `list_migration_logs`, `get_migration_gap_report`, `create_screen_from_dynaform`, `link_migration_assets`, `apply_task_assignments`, `apply_abe_configuration`, `create_collection_from_report_table` |
| Read | `list_processes`, `get_process`, `get_process_inventory`, `get_process_bpmn`, `list_screens`, `get_screen`, `list_scripts`, `get_script`, `analyze_process` |
| Design | `apply_process_design`, `create_process`, `update_process`, `create_screen`, `create_screen_from_fields`, `create_script`, `add_bpmn_user_task`, `add_bpmn_sequence_flow`, `add_bpmn_exclusive_gateway`, `apply_process_variables` |
| Fix | `validate_process`, `update_process_bpmn`, `link_screen_to_task`, `link_script_to_task` |

## Bundle optional fields (PM4 import)

```json
{
  "subprocess_map": { "CHILD-UID-PM3": "42" },
  "subprocess_start_event_map": { "CHILD-UID-PM3": "node_2" },
  "email_server_map": { "SMTP-UID-PM3": "1" }
}
```

## Migration import steps (log checkpoints)

`create_process` → `create_scripts` → `create_screens` → `link_assets` → `apply_assignments` → `apply_variables` → `bpmn_enhance` → `store_metadata` → `web_entry` → `step_triggers` → `collections` → `abe` → `finalize`

Log path: `storage/app/migration-logs/{pro_uid}.json`

## Cursor MCP config

See `MCPReadme.md` § Cursor setup. PM3 launcher: `processmaker3/workflow/engine/bin/mcp-migration-reader.sh`.

PM3 optional env: `PM_WORKSPACE`, `PM3_PATH_DATA`, `PM3_MCP_DB_HOST`, `PM3_MCP_DB_PORT`, `PM3_PHP`.

## Tests

```bash
# PM4
./vendor/bin/phpunit tests/unit/Mcp/ tests/Feature/Mcp/Migration/WriterTest.php

# PM3
./vendor/bin/phpunit tests/unit/ProcessMaker/Mcp/Migration/
```
