# Skill: Web Entry (`pm:config.web_entry`)

Use when configuring anonymous or authenticated public URLs on a Start Event or Form Task. Registered by `package-webentry` via `modeler-init`.

## Where it lives

Nested JSON inside the element’s **`pm:config`** attribute (sibling keys like `email_notifications` / `selfService` must be preserved):

```json
{
  "web_entry": { /* object = enabled */ },
  "selfService": false
}
```

`web_entry: null` means **disabled**. Do not replace null with `{}`.

## Modes

`DISABLED` | `ANONYMOUS` | `AUTHENTICATED`

Anonymous forces `assignment` + `assignedUsers` to the anonymous user id on that node.

## Fields

| Field | Start | Task | Purpose |
|-------|-------|------|---------|
| `screen_id` | yes (required when enabled) | — | FORM screen for start |
| `completed_action` | yes | yes | `SCREEN` \| `URL` |
| `completed_screen_id` | if SCREEN | if SCREEN | DISPLAY after submit |
| `completed_url` | if URL | if URL | Redirect (mustache OK) |
| `enable_query_params` | yes | — | Seed request from query |
| `require_valid_session` | — | Anonymous | Session gate |
| `enable_password_protect` / `password` | Anonymous | Anonymous | Password gate |
| `exclude_data` | — | yes | Vars stripped from browser payload |
| `embed` | yes | yes | `{position,title,icon,color}` |
| `webentryRouteConfig` | yes | yes | `{nodeId,processId,urlType,entryUrl,parameters,…}` |

## URLs

- Start: `/webentry/{processId}/{nodeId}` (or custom `entryUrl` / `firstUrlSegment`)
- Task: `/webentry/request/{{ _request.id }}/{nodeId}`

`entryUrl` and `webentryRouteConfig.processId` are **environment-specific** — do not copy between instances.

## Portable refs (PLG)

In git BPMN, use `${screen:title}` for `web_entry.screen_id` and `web_entry.completed_screen_id`. Deploy resolves those two fields. Other nested ids stay numeric.

## Task vs Start

- **Start:** Web Entry owns the start FORM via `screen_id`
- **Task:** primary UI is task `pm:screenRef`; `web_entry.screen_id` is usually null / secondary

## Validation rules

- `completed_action=URL` → `completed_url` required
- `completed_action=SCREEN` → `completed_screen_id` required
- Password protect → password required
- Parallel multi-instance loop → Web Entry disabled
- Custom route `firstUrlSegment` must be unique across processes

## Pitfalls

1. Forgetting Web Entry is inside `pm:config`, not a top-level `pm:webEntry` attr
2. Anonymous assignment wiping real assignees
3. Pointing CallActivity at a Web Entry–only start
4. Rewriting `processId` / `entryUrl` after pull “to match” — leave them; use named screen refs only where supported
