# Skill: Service Task & package connectors

Use when adding a Service Task or a package connector node (email, PDF, AI/FlowGenie, Data Connector, Decision Engine, Slack, DocuSign, IDP, …).

## Identity

| | |
|--|--|
| Base modeler id | `processmaker-modeler-service-task` |
| BPMN type | `bpmn:ServiceTask` |
| Real work | BPMN `implementation` + JSON `config` / `pm:config` |

Base inspector is Name only. Packages register palette nodes on `modeler-init` with a fixed `implementation` key.

## Common package implementations

| Palette / modeler id | `implementation` |
|----------------------|------------------|
| Send Email | `connector-send-email/processmaker-communication-email-send` |
| PDF | `connector-pdf-print/processmaker-communication-pdf-print` |
| Data Connector | `package-data-sources/data-source-task-service` |
| Decision table | `package-decision-engine/decision-table` |
| FlowGenie / AI | `package-ai/processmaker-ai-task` |
| Slack / DocuSign / IDP | package-specific keys |

Also related: Data Connector CallActivity (`data-source-call-activity`), Actions By Email CallActivity.

## Example (email)

```xml
<bpmn:serviceTask id="node_90" name="Notify Applicant"
  implementation="connector-send-email/processmaker-communication-email-send"
  pm:config="{&quot;emailServer&quot;:&quot;&quot;,&quot;type&quot;:&quot;text&quot;,&quot;subject&quot;:&quot;Update&quot;,&quot;textBody&quot;:&quot;Hello&quot;,&quot;toRecipients&quot;:[{&quot;email&quot;:&quot;{{ email }}&quot;,&quot;type&quot;:&quot;email&quot;}]}" />
```

Typical email config keys: `emailServer`, `type` (`text`|`screen`), `subject`, `textBody`, `screenRef`, `toRecipients[]`, `cc`/`bcc`, `attachments[]`.

## Data in / out

- **In:** request data + decoded config → connector PHP class / script
- **Out:** implementation return merged into request (connector-specific)
- Error handling: `pm:errorHandling` where supported

## Validation

- FlowGenie: `validateFlowGenieRef` when `implementation` is `package-ai/processmaker-ai-task`
- Missing `implementation` → no-op / runtime failure
- `config` must be valid JSON inside the XML attribute

## Form Task email notifications

`connector-send-email` can register an inspector accordion on **Form Tasks** writing `pm:config.email_notifications` — that is not a Service Task; do not confuse the two.

## Pitfalls

1. Connector package must be installed on the instance
2. Do not invent implementation strings
3. Email `screenRef` inside connector config ≠ Form Task `pm:screenRef`
4. Prefer documenting instance-specific server ids in workflow notes, not inventing them
