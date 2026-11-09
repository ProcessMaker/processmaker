# Skill: Form Task (`bpmn:Task`)

Use when adding or configuring a Form Task (human interactive task with a FORM screen).

## Identity

| | |
|--|--|
| Modeler id | `processmaker-modeler-task` |
| BPMN type | **`bpmn:Task`** (never invent `bpmn:UserTask` for Modeler XML) |
| Default | `pm:assignment="requester"` |

## Inspectors → attributes

| UI | Attr / location | Required | Notes |
|----|-----------------|----------|-------|
| Name | `name` | yes | |
| Screen for Input | `pm:screenRef` | **yes** | Screen type `FORM` or `CONVERSATIONAL` |
| Due In | `pm:dueIn`, `dueInVariable`, `isDueInVariable` | no | Hours or variable |
| Assignment Rules | `pm:assignment`, `assignedUsers`, `assignedGroups`, `assignmentRules`, `assignmentLock`, `allowReassignment` | practically yes | See types below |
| Self Service | `pm:config.selfService` | no | With `user_group` |
| Notifications | `pm:config.email_notifications` (email package) | no | |
| Interstitial | `pm:allowInterstitial`, `pm:interstitialScreenRef` | conditional | Modeler: “Display Next Assigned Task…”. If `allowInterstitial="true"`, **`interstitialScreenRef` is required** (DISPLAY screen, often key `interstitial`). Missing ref → UI stuck loading; next task never opens. Prefer omit interstitial unless you set both attrs. |
| Element Destination | `pm:elementDestination` | no | After submit |
| Conditional Redirect | `pm:conditionalRedirect` | no | Expression → destination |
| Web Entry | `pm:config.web_entry` | no | See `process-web-entry` |
| Loop | `loopCharacteristics` | no | Multi-instance; parallel MI disables Web Entry |

## Assignment types

`requester` | `user_group` | `previous_task_assignee` | `process_variable` | `rule_expression` | `process_manager`

- `user_group`: CSV ids in `assignedUsers` / `assignedGroups`
- `process_variable`: **expression strings** in those attrs (e.g. `[department_id]`), not only numeric ids
- Web Entry ANONYMOUS forces assignment to anonymous user and disables assignment UI

## Example

```xml
<bpmn:task id="node_10" name="Review Application"
  pm:screenRef="${screen:college-review}"
  pm:assignment="user_group"
  pm:assignedUsers=""
  pm:assignedGroups="3"
  pm:assignmentLock="false"
  pm:allowReassignment="false"
  pm:dueIn="24"
  pm:config="{&quot;web_entry&quot;:null,&quot;selfService&quot;:false}" />
```

## Data in / out

- **In:** full request data bound into Screen Builder
- **Out:** submitted fields **merged** into request data; token completes

Watchers/computed run client-side on the screen.

## Validation

Modeler: Task without `screenRef` → “Please select an input screen”.

## Pitfalls

1. Screen must be FORM (not DISPLAY — that is Manual Task)
2. Element type must be `bpmn:Task`
3. Prefer `${screen:title}` in **git** BPMN (PLG deploy). For MCP create/update-process use **numeric** screen id from `create-screen` / `list-screens`
4. Nested JSON in `pm:config` must be XML-escaped
5. Do not confuse task `pm:screenRef` with Web Entry `web_entry.screen_id` (start-only); task Web Entry uses the task screen
6. Never enable interstitial (`allowInterstitial="true"`) without `interstitialScreenRef` (DISPLAY). See skill `process-node-start-event`
7. Mirror every sequence flow as `<bpmn:outgoing>` / `<bpmn:incoming>` on this task
