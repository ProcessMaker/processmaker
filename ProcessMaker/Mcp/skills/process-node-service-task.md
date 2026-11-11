# Skill: Service Task & package connectors

Use when adding a Service Task or choosing which package connector skill to load. This skill is an **index** — load the dedicated connector skill for config details.

## Identity

| | |
|--|--|
| Base modeler id | `processmaker-modeler-service-task` |
| BPMN type | `bpmn:ServiceTask` |
| Real work | BPMN `implementation` + JSON `config` / `pm:config` |

Base inspector is Name only. Packages register palette nodes on `modeler-init` with a fixed `implementation` key.

## Connector skills (load these)

| Palette | `implementation` | Skill slug |
|---------|------------------|------------|
| Send Email | `connector-send-email/processmaker-communication-email-send` | `process-connector-email` |
| PDF Generator | `connector-pdf-print/processmaker-communication-pdf-print` | `process-connector-pdf` |
| Slack Notification | `connector-slack/processmaker-communication-slack` | `process-connector-slack` |
| Data Connector | `package-data-sources/data-source-task-service` | `data-connector-in-process` |
| Decision Task | `package-decision-engine/decision-table` | `process-connector-decision-task` |
| FlowGenie | `package-ai/processmaker-ai-task` | `process-connector-flowgenie` |
| UiPath | `package-rpa/processmaker-communication-rpa` | `process-connector-uipath` |
| IDP | `connector-idp/processmaker-communication-idp` | _(no dedicated skill yet)_ |

### Call Activity connectors (not Service Task)

| Palette | Marker | Skill slug |
|---------|--------|------------|
| Actions By Email | `calledElement=ProcessId-{id}` | `process-connector-actions-by-email` |
| DocuSign | `calledElement=ProcessId-DocuSignSendEnvelope` | `process-connector-docusign` |
| Data Connector callback | `calledElement=data_source_callback-data_source_sub_process` | `data-connector-in-process` |

## Data in / out

- **In:** request data + decoded config → connector PHP class / script
- **Out:** implementation return merged into request (connector-specific)
- Error handling: `pm:errorHandling` where supported (Data Connector, UiPath, …)

## Validation

- FlowGenie: modeler `validateFlowGenieRef` when `implementation` is `package-ai/processmaker-ai-task`
- Missing `implementation` → no-op / runtime failure
- `config` must be valid JSON inside the XML attribute
- MCP `validate-process` does **not** yet verify connector asset refs

## Pitfalls

1. Connector package must be installed on the instance
2. Do not invent implementation strings — copy from the table above
3. Load the dedicated skill before authoring `pm:config`
4. Email connector `screenRef` ≠ Form Task `pm:screenRef`
