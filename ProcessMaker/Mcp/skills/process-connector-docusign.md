# Skill: DocuSign connector

Use when adding a DocuSign envelope node. This is a **Call Activity**, not a Service Task.

## Identity

| | |
|--|--|
| Modeler id | `docusign-template` |
| BPMN type | `bpmn:CallActivity` |
| `calledElement` | `ProcessId-DocuSignSendEnvelope` |
| Interstitial | `pm:allowInterstitial="true"`, `pm:interstitialScreenRef="DocuSignWait"` |
| Config | `pm:config` (JSON string) |
| Package | `connector-docusign` |

`identifyElement` requires `contentType === "template"`.

## Discover ids

- DocuSign `templateId` and role metadata from DocuSign settings / templates API on the instance.
- Do not invent template or role ids.
- Ensure DocuSign subprocess / wait screen assets are installed (package seeders).

## `pm:config` shape

```json
{
  "contentType": "template",
  "templateId": "abc-123",
  "documentName": "Contract",
  "templateRoles": [
    {
      "recipientId": "1",
      "roleName": "Signer 1",
      "type": "requester"
    }
  ],
  "waitCompleted": true,
  "outputVariable": "envelope",
  "content": "",
  "screenId": ""
}
```

| Key | Required | Notes |
|-----|----------|--------|
| `contentType` | yes | Must be `template` for this node |
| `templateId` | yes | DocuSign template id |
| `templateRoles` | yes | Role → recipient mapping |
| `documentName` | no | Default `Document` |
| `waitCompleted` | no | Wait for completed signature |
| `outputVariable` | no | Where envelope result is stored |
| `content` / `screenId` | legacy | Prefer templateId + roles |

### Role / recipient (typical)

Overlay fields: `recipientId`, `roleName`, `type` (`user`\|`requester`\|`userId`\|`email`\|`manager`), plus email/name expressions as returned by the DocuSign inspector.

Unsupported in inspector (configure in DocuSign): `witness`, `inpersonsigner`, `notary`.

## Example BPMN

```xml
<bpmn:callActivity id="node_ds" name="DocuSign"
  calledElement="ProcessId-DocuSignSendEnvelope"
  pm:allowInterstitial="true"
  pm:interstitialScreenRef="DocuSignWait"
  pm:config="{&quot;contentType&quot;:&quot;template&quot;,&quot;templateId&quot;:&quot;abc-123&quot;,&quot;documentName&quot;:&quot;Contract&quot;,&quot;templateRoles&quot;:[{&quot;recipientId&quot;:&quot;1&quot;,&quot;roleName&quot;:&quot;Signer 1&quot;,&quot;type&quot;:&quot;requester&quot;}],&quot;waitCompleted&quot;:true,&quot;outputVariable&quot;:&quot;envelope&quot;}" />
```

## Pitfalls

1. Missing interstitial attrs → wait UI can hang (same class of issue as Form Task interstitial).
2. `calledElement` must match the seeded DocuSign subprocess.
3. Package + DocuSign credentials must be configured.
