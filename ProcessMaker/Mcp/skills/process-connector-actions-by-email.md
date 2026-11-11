# Skill: Actions By Email connector

Use when adding an Actions By Email node (interactive email with reply options). This is a **Call Activity**, not a Service Task.

## Identity

| | |
|--|--|
| Modeler id | `processmaker-send-actions-by-email` |
| BPMN type | `bpmn:CallActivity` |
| `calledElement` | `ProcessId-{abeSubProcessId}` (resolved at design time) |
| Config | `pm:config` (JSON string) |
| Package | `package-actions-by-email` |

Identification: `pm:config` contains both `options` and `keyName`.

## Discover ids

1. Call `GET /api/1.0/actions-by-email/get-sub-process` on the instance.
2. Set `calledElement` to `ProcessId-` + returned process `id`.
3. Email servers: `/plugins/email/servers`.
4. Do not invent subprocess ids. Ignore stale default `template: "welcome"` from the palette definition.

## `pm:config` shape

```json
{
  "email": "{{ user_email }}",
  "subject": "Please approve",
  "emailServer": "",
  "selectedLanguage": "en",
  "langVariable": null,
  "bodyType": "text",
  "body": "Choose an action",
  "screenRef": "",
  "keyName": "abe_response",
  "options": [
    { "value": "approve", "content": "Approve", "buttonVariant": "btn-success" },
    { "value": "reject", "content": "Reject", "buttonVariant": "btn-danger" }
  ],
  "startEvent": "subprocess_start"
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `email` | yes | Recipient; Mustache OK |
| `subject` | yes | |
| `keyName` | yes | Request data key for the reply |
| `options` | yes | Action buttons |
| `bodyType` | yes | `text` \| `screen` |
| `body` | if text | |
| `screenRef` | if screen | EMAIL screen id |
| `emailServer` | no | `""` = default |
| `selectedLanguage` | no | `en`\|`de`\|`es`\|`fr`\|`variable` |
| `langVariable` | if variable | Process var for locale |
| `startEvent` | default | `subprocess_start` |

### Option item

`{ "value", "content", "buttonVariant" }` — `buttonVariant` like `btn-primary`, `btn-success`, `btn-danger`.

## Example BPMN

```xml
<bpmn:callActivity id="node_abe" name="Actions By Email"
  calledElement="ProcessId-123"
  pm:config="{&quot;email&quot;:&quot;{{user_email}}&quot;,&quot;subject&quot;:&quot;Approve&quot;,&quot;bodyType&quot;:&quot;text&quot;,&quot;body&quot;:&quot;Please choose&quot;,&quot;keyName&quot;:&quot;abe_response&quot;,&quot;options&quot;:[{&quot;value&quot;:&quot;approve&quot;,&quot;content&quot;:&quot;Approve&quot;,&quot;buttonVariant&quot;:&quot;btn-success&quot;}],&quot;emailServer&quot;:&quot;&quot;,&quot;selectedLanguage&quot;:&quot;en&quot;,&quot;startEvent&quot;:&quot;subprocess_start&quot;}" />
```

## Related

- Plain outbound email → skill `process-connector-email`.
- Form Task ABE toggle (`pm:isActionsByEmail` / `pm:configEmail`) → also documented in `process-connector-email`.

## Pitfalls

1. Wrong or empty `calledElement` → Call Activity never resolves.
2. Requires IMAP / ABE package configuration for replies to complete.
3. Package must be installed and subprocess seeded.
