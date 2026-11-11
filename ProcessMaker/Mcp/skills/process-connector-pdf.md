# Skill: PDF Generator connector

Use when adding a PDF Generator Service Task (`connector-pdf-print`).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-communication-pdf-print` |
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `connector-pdf-print/processmaker-communication-pdf-print` |
| Config | `pm:config` (JSON string) |
| Package | `connector-pdf-print` |

New nodes may start with empty `config` — always set both keys before save.

## Discover ids

- `screenRef`: existing Screen id (DISPLAY or suitable print layout). Use Screens MCP; do not invent ids.

## `pm:config` shape

```json
{
  "screenRef": 42,
  "fileName": "invoice-{{id}}"
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `screenRef` | yes | Screen to render as PDF |
| `fileName` | yes | Mustache OK; chars `A-Za-z0-9_.-` + `{{vars}}`; no trailing `.` |

## Example BPMN

```xml
<bpmn:serviceTask id="node_pdf" name="PDF Generator"
  implementation="connector-pdf-print/processmaker-communication-pdf-print"
  pm:config="{&quot;screenRef&quot;:42,&quot;fileName&quot;:&quot;invoice-{{id}}&quot;}" />
```

## Pitfalls

1. Empty `config` on a new node → runtime failure; always set `screenRef` + `fileName`.
2. Invalid filename characters fail at generate time.
3. Package `connector-pdf-print` must be installed.
