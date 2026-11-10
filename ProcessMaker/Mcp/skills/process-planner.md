# Skill: Process planner / designer

Use when the human asks to create or design a process but requirements are incomplete (missing tasks, decisions, assignees, screens, or start mode).

## Mandatory first step

1. Call **`plan-process`** with at least `goal` (their request in free text).
2. If `ready` is `false`, **ask the human** the `open_questions` (group related ones). Do **not** invent assignments or invent user/group ids.
3. For assignee questions, call **`list-users`** and/or **`list-groups`**, show a short list (id + name), and let the human pick.
4. Call **`plan-process` again** with the same `goal` plus every answer field you collected (`name`, `purpose`, `tasks`, `decisions`, `assignments`, `process_manager`, `screens`, `start`).
5. When `ready` is `true`, follow `blueprint.next_steps`: screens → BPMN (load `process-creation-rules` + node skills) → `validate-process` → `create-process`.

## Assignment cheat sheet

| Type | BPMN | How to pick |
|------|------|-------------|
| Requester | `pm:assignment="requester"` | No directory lookup |
| Process manager | `pm:assignment="process_manager"` | `list-users` + set process manager on the process |
| Specific users | `pm:assignment="user_group"` + `pm:assignedUsers="1,2"` | `list-users` |
| Groups | `pm:assignment="user_group"` + `pm:assignedGroups="3"` | `list-groups` |

## Do not

- Skip `plan-process` on vague requests (“build me a leave process”)
- Hardcode user/group ids without listing them to the human
- Call `create-process` while `ready` is false
