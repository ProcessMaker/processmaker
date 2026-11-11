# Skill: FlowGenie creation rules

Load before `create-flow-genie`, `update-flow-genie`, or `validate-flow-genie`.

A **FlowGenie** is a Designer asset (`flow_genies` table) that defines an AI agent: system instructions, model/provider, tools/MCP servers, response format, and guardrails. Process Service Tasks reference it by numeric `flow_genie_id` (see skill `process-connector-flowgenie`).

## Required fields

| Field | Notes |
|-------|--------|
| `name` | Unique; `alpha_spaces` |
| `description` | Required, max 300 |
| `flow_genie_category_id` | From `list-flow-genie-categories` (comma-separated ids OK) |

Optional: `config` (object). If omitted, MCP applies agent defaults (empty `instructions` until you update).

## `config` shape (agent schema)

Prefer the Designer / `FlowGenieEdit` keys:

```json
{
  "instructions": "You summarize the request and suggest APPROVED or REJECTED as JSON.",
  "model": "gpt-4o",
  "provider": "openai",
  "provider_api_key": "",
  "output_type": "JSON",
  "mcp_servers": [],
  "tools": [],
  "dynamic_tools": [],
  "collections": [],
  "subagents": [],
  "max_turns": 30,
  "limit": 15,
  "match_threshold": 0.3,
  "enhance_pdf_quality": false,
  "model_settings": {
    "temperature": 0.5,
    "top_p": 1,
    "tool_choice": "auto",
    "parallel_tool_calls": false
  },
  "guardrails": {
    "enabled": true,
    "policies": []
  }
}
```

| Key | Required for runtime | Notes |
|-----|----------------------|--------|
| `instructions` | **yes** (practically) | System prompt. Legacy alias: `prompt` (MCP normalizes → `instructions`) |
| `model` | recommended | Default from `config('ai.default_model')` or `gpt-4o` |
| `provider` | recommended | e.g. `openai` |
| `output_type` | recommended | `Text (default)` \| `JSON` \| `Table`. Aliases: `response_format` / `responseFormat` (`text`\|`json`\|`table`) |
| `mcp_servers` | no | Array of MCP server defs (auth secrets write-only) |
| `tools` / `dynamic_tools` | no | Built-in / dynamic tool lists |
| `collections` | no | RAG collection ids |
| `model_settings` | no | temperature, tool_choice, max_tokens, … |
| `guardrails` | no | Normalized on save (`enabled` + `policies`) |

Do **not** invent BPMN `implementation` strings here — this skill only authors the **asset**. Wiring into a process is `process-connector-flowgenie`.

## Workflow

1. `list-flow-genies` — avoid duplicate names.
2. `list-flow-genie-categories` — pick `flow_genie_category_id`.
3. Draft `config` with a clear `instructions` prompt and `output_type`.
4. `validate-flow-genie` — fix `errors[]`; treat missing instructions as a warning until set.
5. `create-flow-genie` (or `update-flow-genie` with full `config` replace).
6. `get-flow-genie` — confirm numeric `id` and `has_instructions`.
7. In Processes MCP: Service Task `package-ai/processmaker-ai-task` with `pm:config` `{"flow_genie_id":<id>,"output_variable":"ai_suggestion"}`.

## Secrets

- `provider_api_key` and MCP auth fields are **write-only**.
- `get-flow-genie` / `list-flow-genies` redact them as `[redacted]`.
- Prefer instance-level AI provider settings when possible; only set per-genie keys when required.

## Pitfalls

1. Empty `instructions` → agent runs with a blank system prompt.
2. Creating a genie without wiring `flow_genie_id` into BPMN leaves the Service Task invalid in Modeler.
3. `update-flow-genie` with `config` **replaces** the whole config after normalization (merge defaults + payload) — send the full intended config.
4. `package-ai` must be installed; AI microservice must be reachable for runtime runs.
5. Category is required — create one in Designer if `list-flow-genie-categories` is empty.
