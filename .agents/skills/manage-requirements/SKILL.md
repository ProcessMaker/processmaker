---
name: manage-requirements
description: >-
  Author and validate FR/NFR requirement catalogs (MD + YAML) for any TCE
  project. Use when creating, migrating, editing, or validating requirements,
  specs with acceptance criteria, requirements.yaml, FR-/NFR- IDs, or
  component/screen/script requirement catalogs.
---

# Manage requirements

Cross-project skill. Schemas live here and apply to CollegeV2, Military, High School, Shared, or any new module.

## File contract

Each feature is a folder with exactly two artifacts:

| File | Role |
| --- | --- |
| `<feature>.md` (or `README.md`) | Human/AI narrative: purpose, actors, config, links |
| `requirements.yaml` | Machine-checked catalog (`requirements[]`) |

Schemas (source of truth):

- [schemas/requirement.schema.json](schemas/requirement.schema.json) — single Requirement
- [schemas/requirements-catalog.schema.json](schemas/requirements-catalog.schema.json) — catalog document

Templates: [templates/](templates/).

## Catalog shape

```yaml
specId: my-feature
kind: component   # component | screen | script
title: My feature
config: {}        # props / ports / section ids — keep FRs reusable
dependsOn: []     # other specId values
requirements:
  - id: FR-001
    requirementType: Functional
    title: ...
    statement: ...
    priority: High
    status: Draft
    acceptanceCriteria:
      - given: ...
        when: ...
        then: ...
```

Rules:

- Local IDs `FR-NNN` / `NFR-NNN` unique **within** one catalog
- Cross-feature refs: `specId#FR-001` in `dependencies`
- Functional → `acceptanceCriteria` required
- NonFunctional → `category`, `metrics`, `verificationMethod` required
- Edge-case scenario IDs (e.g. `CE-001`) go in `traceability.testCases` only

## Authoring by kind

1. **component** — Behavior given `config`. No screen names in `statement` / `acceptanceCriteria`. Screens list the component in `dependsOn` and describe their config in the MD.
2. **script** — Domain outcomes via named ports. No vendor URLs or ProcessMaker HTTP in statements; adapters belong in `config` / `assumptions`.
3. **screen** — Workflow composition, permissions, Continue rules. Reuse components via `dependsOn`; do not duplicate component FRs.

### CollegeV2 course comments (`comments_log`)

- Prefer a **component** catalog for shared `CommentsLogTable` (read-only thread); screen RFs `dependsOn` it and describe when the section is visible (`comments_log.length > 0`).
- In screen MD **assumptions**, state whether comments are stored in **case JSON** (`course.comments_log[]`), appended only on **save** from a draft field (`comments_note` / student `comments`), and/or loaded later via **`acol_applicant_course_comments`** HTTP — avoid contradictory `then` clauses across catalogs.
- Acceptance criteria should spell out **role** (`tco`, `student`), **read vs write**, and **append-on-save** (draft is not in the log until save succeeds).
- Multi-external articulation: if one save updates linked courses, say whether companions receive the same `comments_log` entries or only articulation/inbox fields.

Suggested project layout (optional; any tree of `requirements.yaml` validates):

```text
ai-docs/<project>/requirements/
  components/<specId>/{<specId>.md,requirements.yaml}
  screens/<specId>/{<specId>.md,requirements.yaml}
  scripts/<specId>/{<specId>.md,requirements.yaml}
```

## Validate

Primary gate (schema + heuristic quality):

```bash
npm run check:requirements                 # all ai-docs/*/requirements
npm run check:requirements -- college-v2   # one project
npm run check:requirements -- military
```

Schema-only (path optional; default is every `ai-docs/*/requirements` tree):

```bash
npm run validate:requirements
npm run validate:requirements -- path/to/requirements.yaml
npm run validate:requirements -- path/to/feature-or-tree
```

Schema checks required fields, unique IDs, `dependsOn`, companion markdown (`<specId>.md` or `README.md`), and non-empty Given/When/Then. Tautological acceptance criteria (placeholder given/when, or `then` repeating the title) are warnings, not hard failures.

Quality harness (any project) lives in [quality/](quality/). Project text fixtures stay under `ai-docs/<project>/quality/` (`*.yaml` questions, optional `vocabulary.md`). `check:requirements` / `judge_catalog.py` exits non-zero while any FR fails (for example stub acceptance criteria).

Harness unit tests (tooling, not catalog gates):

```bash
npm run test:requirements-schema
npm run test:requirements-quality
```

Laya (downloads a local checkpoint):

```bash
# pip install -r .agents/skills/manage-requirements/quality/requirements.txt
# REQUIREMENTS_PROJECT=college-v2 LAYA_SOURCE=laya .agents/skills/manage-requirements/quality/run_laya_eval.sh
```

Global tests are `ai-docs/<project>/quality/*.yaml` with `applies.all`. Domain extras are more YAML in that folder with `applies.ids` (or kinds/tags).

Checklist before finishing:

- [ ] MD + `requirements.yaml` pair exists
- [ ] `npm run check:requirements -- <project>` passes (or `validate:requirements -- <path>` for schema-only)
- [ ] IDs unique in catalog; Functional/NonFunctional fields complete
- [ ] Component/script statements stay screen-/vendor-agnostic
- [ ] Screen catalogs `dependsOn` shared components instead of copying FRs
- [ ] Edge cases linked via `traceability.testCases` when known

## Create / migrate workflow

1. Choose `kind` and `specId` (stable folder basename).
2. Copy templates from `templates/`; rename the MD to match `specId`.
3. Extract FRs from existing design docs / edge cases; map scenarios to acceptance criteria and `testCases`.
4. For screens that embed a reusable UI, create/reuse a **component** catalog first, then `dependsOn` it.
5. Validate; fix schema errors before committing.
