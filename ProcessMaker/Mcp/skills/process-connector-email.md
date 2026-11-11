# Skill: Send Email connector

Use when adding a Send Email Service Task, or Form Task email notifications / Actions-by-Email toggles from `connector-send-email`.

## Identity

| | |
|--|--|
| Modeler id | `processmaker-communication-email-send` |
| BPMN type | `bpmn:ServiceTask` |
| `implementation` | `connector-send-email/processmaker-communication-email-send` |
| Config | `pm:config` (JSON string) |
| Package | `connector-send-email` |

## Discover ids

- Email servers: instance API `/plugins/email/servers` (empty `emailServer` = default).
- `screenRef`: EMAIL-type screen id (Screens MCP `list-screens` / `create-screen`).
- Do not invent server or screen ids.

## `pm:config` shape (Service Task)

```json
{
  "emailServer": "",
  "type": "text",
  "subject": "Update {{name}}",
  "textBody": "Hello",
  "screenRef": null,
  "toRecipients": [{ "type": "email", "value": "{{ applicant_email }}" }],
  "ccRecipients": [],
  "bccRecipients": [],
  "attachments": []
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `emailServer` | no | Server id string; `""` = default |
| `type` | yes | `text` or `screen` |
| `subject` | yes | Mustache OK |
| `textBody` | if `type=text` | Body text |
| `screenRef` | if `type=screen` | EMAIL screen id |
| `toRecipients` / `ccRecipients` / `bccRecipients` | To recommended | See recipient shape |
| `attachments` | no | See attachment shape |
| `users` / `groups` | legacy | Prefer recipients arrays |

### Recipient

`{ "type": "<kind>", "value"?: ... }`

| `type` | `value` |
|--------|---------|
| `email` | string (mustache OK) |
| `userId` | string / mustache |
| `usersAndGroups` | `{ "users": [{ "id", "fullname" }], "groups": [{ "id", "name" }] }` |
| `requester` / `participants` / `assignedUser` / `manager` | omit `value` |

### Attachment

`{ "source": "variable"|"url"|"array", "value": string, "variableInArray"?: string }`

## Example BPMN

```xml
<bpmn:serviceTask id="node_email" name="Notify Applicant"
  implementation="connector-send-email/processmaker-communication-email-send"
  pm:config="{&quot;emailServer&quot;:&quot;&quot;,&quot;type&quot;:&quot;text&quot;,&quot;subject&quot;:&quot;Update&quot;,&quot;textBody&quot;:&quot;Hello&quot;,&quot;screenRef&quot;:null,&quot;toRecipients&quot;:[{&quot;type&quot;:&quot;requester&quot;}],&quot;ccRecipients&quot;:[],&quot;bccRecipients&quot;:[],&quot;attachments&quot;:[]}" />
```

## Form Task extensions (not Service Task)

### Email notifications accordion

On `bpmn:Task` Form Tasks, `pm:config.email_notifications`:

```json
{
  "email_notifications": {
    "notifications": [
      {
        "sendAt": "task-start",
        "expression": "",
        "subject": "",
        "type": "text",
        "textBody": "",
        "screenRef": null,
        "toRecipients": [],
        "ccRecipients": [],
        "bccRecipients": [],
        "attachments": []
      }
    ]
  }
}
```

`sendAt`: `task-start` | `task-end`.

### Form Task Actions By Email

- `pm:isActionsByEmail="true"`
- `pm:configEmail`: `{ "emailServer", "subject", "screenEmailRef", "requireLogin", "screenCompleteRef" }`
- Recipients come from task assignment, not `toRecipients`.

Core assignee/requester notification toggles live on process `task_notifications[nodeId]`, not `pm:config`.

## Pitfalls

1. Package must be installed.
2. Never invent `implementation` strings.
3. Connector `screenRef` ≠ Form Task `pm:screenRef`.
4. Prefer `toRecipients` over legacy `users` / `groups` / `addEmails`.
5. For interactive email replies use skill `process-connector-actions-by-email` (Call Activity), not this Service Task.
