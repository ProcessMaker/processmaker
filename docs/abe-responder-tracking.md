# Actions by Email: Responder Tracking

This document describes **Track responder identity** for Actions by Email (ABE): personalized signed links and the nested `abe_responder` object written into request data when someone acts from an email.

## Overview

By default, ABE sends a **shared** action link to all recipients. Anyone with the link can complete the task, and ProcessMaker cannot reliably tell which recipient clicked.

When **Track responder identity** is enabled, ProcessMaker instead:

1. Resolves unique recipients from the task assignment rules
2. Sends **one personalized email per unique email address**
3. Rewrites ABE action URLs in that email with a **signed query string** identifying the recipient (and assigned groups)
4. On click, writes an **`abe_responder`** object into request data for downstream use

Token-row fields such as `process_abe_request_tokens.user_id` and `answered_at` continue to work as before. Responder tracking adds request-data metadata; it does not replace those columns.

## Enabling the feature

In the Modeler, open a Form Task with **Actions by Email** enabled.

1. Turn on **Actions by Email**
2. Configure subject, email screen, require login, and completed screen as usual
3. Enable **Track responder identity**

The control is stored on the task as `pm:configEmail` JSON:

```json
{
  "subject": "RE: Form Task",
  "emailServer": "",
  "requireLogin": false,
  "screenEmailRef": 142,
  "screenCompleteRef": 10,
  "trackResponder": true
}
```

| `trackResponder` | Behavior |
| --- | --- |
| `false` / omitted (default) | Legacy shared link; **no** `abe_responder` in request data |
| `true` | Personalized signed emails; `abe_responder` written on click |

## How sending works

### Recipient resolution

Recipients come from assignment rules (assigned user, or self-service users/groups):

- Inactive / out-of-office users are excluded
- A user in **multiple assigned groups** receives **one** email; those groups are merged into the signed payload
- If two different users share the **same email address**, only **one** email is sent (first user by id wins)

### Personalized links

Each email’s ABE `/tasks/update_variable/{token_abe}` links are updated automatically to append signed parameters:

| Param | Meaning |
| --- | --- |
| `abe_email` | Recipient email (normalized) |
| `abe_user_id` | ProcessMaker user id |
| `abe_groups` | Base64 JSON of `[{ "id", "name" }, ...]` |
| `abe_sig` | HMAC-SHA256 over token + email + user id + groups (using `app.key`) |

Authors do not need to change email screens: known ABE action URLs are rewritten when the email HTML is built.

### Staggered dispatch

Personalized sends are queued one job per recipient. Dispatches are staggered by default (`index × 1` second) to reduce provider “emails per second” throttling.

Optional config:

```php
// config/app.php or runtime config
'abe_email_stagger_seconds' => 1,
```

## How clicks update request data

`TaskController::updateVariable` always sets the action variable (`varName` / `varValue`).

**Only when `trackResponder` is true** on the task’s `configEmail`, it also sets:

```text
abe_responder
```

### `abe_responder` shape

```json
{
  "email": "user@example.com",
  "user_id": 42,
  "groups": [{ "id": 6, "name": "Approvers" }],
  "acted_at": "2026-11-14T00:15:30+00:00",
  "ip_address": "203.0.113.10",
  "user_agent": "Mozilla/5.0 ...",
  "device": "desktop",
  "email_client": "Gmail",
  "authenticated_email": "user@example.com",
  "authenticated_user_id": 42,
  "identity_mismatch": false
}
```

| Field | Required / when present | Description |
| --- | --- | --- |
| `email` | When signature verifies | Signed-link recipient email |
| `user_id` | When signature verifies | Signed-link user id |
| `groups` | When signature verifies | Assigned groups from the signature |
| `acted_at` | Always (when tracking on) | ISO-8601 action timestamp |
| `ip_address` | Always (when tracking on) | Request IP |
| `user_agent` | Always (when tracking on) | Raw User-Agent |
| `device` | Always (when tracking on) | Best-effort: `mobile`, `desktop`, or `unknown` |
| `email_client` | Always (when tracking on) | Best-effort parse (Gmail, Outlook, Apple Mail, …, Unknown) |
| `authenticated_email` | When a real user is logged in | Session user’s email |
| `authenticated_user_id` | When a real user is logged in | Session user’s id |
| `identity_mismatch` | When a real user is logged in | `true` if authenticated email ≠ signed `email` |

Notes:

- `email` is **always** the signed recipient when the signature is valid—not the logged-in user’s email.
- If the clicker is logged in as a different person, the action is **still allowed**; mismatch is recorded via `identity_mismatch` plus `authenticated_*`.
- Anonymous / public ABE sessions are not treated as authenticated identity.
- **Unsigned / legacy links** (no valid signature): action still succeeds; `abe_responder` is sparse (timestamp, IP, UA, device, email client—and authenticated fields if logged in). No signed `email` / `user_id` / `groups`.

When `trackResponder` is **false**, request data contains only the action field (for example `res: "yes"`)—**not** `abe_responder`.

## Downstream usage

After the task completes, process request data includes `abe_responder` (when tracking was on). Use it in:

- Scripts / data connectors
- Conditional flows
- Collections / reports
- Completed screens

Example Mustache / data path:

```text
{{abe_responder.email}}
{{abe_responder.acted_at}}
{{abe_responder.identity_mismatch}}
```

## Limitations and operational risks

Personalized tracking means **N recipients ≈ N email sends**.

Large assignment groups can hit:

- Per-second rate limits (e.g. Mailtrap testing: `550 5.7.0 Too many emails per second`)
- Daily external-send quotas (e.g. Microsoft `*.onmicrosoft.com`: `550 5.7.236`)
- Provider reputation / spam filtering under burst volume

Recommendations:

- Prefer production ESPs or a custom sending domain (not `onmicrosoft.com` test mailboxes) for large groups
- Keep stagger enabled; increase `abe_email_stagger_seconds` if needed
- Avoid large groups of fake/external addresses in test tenants
- Expect slower overall delivery as group size grows (staggered queue)

Other client/security notes:

- Corporate link wrappers (Safe Links, etc.) usually preserve query params; if `abe_sig` is stripped, the click falls back to sparse `abe_responder`
- `device` / `email_client` are best-effort User-Agent heuristics, not definitive device attestation

## Related code

| Area | Location |
| --- | --- |
| UI opt-in | `connector-send-email` → `resources/js/connectors/email/send/ActionsByEmail.vue` |
| Recipient resolution + send | `ProcessMaker/Repositories/TokenRepository.php` |
| Link rewrite + queue send | `ProcessMaker/Mail/TaskActionByEmail.php` |
| Signature | `ProcessMaker/Helpers/AbeRecipientSignature.php` |
| Request-data builder | `ProcessMaker/Helpers/AbeResponder.php` |
| Click handler | `ProcessMaker/Http/Controllers/TaskController.php` (`updateVariable`) |
| Stagger helper | `package-actions-by-email` → `EmailProvider::sendAsync($config, $delaySeconds)` |

## Tests

- `tests/unit/Helpers/AbeRecipientSignatureTest.php`
- `tests/unit/Helpers/AbeResponderTest.php`
- `tests/unit/Mail/TaskActionByEmailLinkRewriteTest.php`
- `tests/Feature/Api/TaskActionByEmailSelfServiceTest.php`
- `tests/Feature/AbeLargeRecipientGroupTest.php`
- `tests/Feature/TaskControllerTest.php` (nested `abe_responder` when enabled; omitted when disabled)
