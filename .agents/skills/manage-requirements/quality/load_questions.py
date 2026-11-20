"""Load quality-question YAML tests (global QC and targeted extras)."""

from __future__ import annotations

import argparse
from pathlib import Path

import yaml

from extract_requirement_states import iter_requirement_rows
from project import add_project_args, load_vocabulary, paths_from_args

LAYA_TYPES = {"noul", "choice", "score"}
CHECK_TYPE = "check"
METADATA_KEYS = {"id", "applies", "exclude", "expected", "checks", "path"}


def _as_list(value) -> list:
    if value is None:
        return []
    if isinstance(value, list):
        return value
    return [value]


def load_question_documents(questions_dir: Path) -> list[dict]:
    if not questions_dir.is_dir():
        raise FileNotFoundError(f"Questions directory not found: {questions_dir}")
    documents = []
    for path in sorted(questions_dir.glob("*.yaml")):
        data = yaml.safe_load(path.read_text(encoding="utf-8")) or {}
        if not isinstance(data, dict):
            raise ValueError(f"{path} must be a YAML mapping")
        qid = data.get("id")
        if qid != path.stem:
            raise ValueError(f"{path.name}: id {qid!r} must match filename stem {path.stem!r}")
        qtype = data.get("type")
        if qtype not in LAYA_TYPES | {CHECK_TYPE}:
            raise ValueError(f"{path.name}: type must be noul, choice, score, or check")
        data = dict(data)
        data["path"] = path
        documents.append(data)
    if not documents:
        raise ValueError(f"No question YAML files in {questions_dir}")
    return documents


def is_global_qc(question: dict) -> bool:
    applies = question.get("applies") or {}
    return bool(applies.get("all")) and question.get("type") in LAYA_TYPES


def laya_payload(question: dict) -> dict:
    payload = {}
    for key, value in question.items():
        if key not in METADATA_KEYS:
            payload[key] = value
    return payload


def global_laya_questions(questions: list[dict]) -> dict:
    return {q["id"]: laya_payload(q) for q in questions if is_global_qc(q)}


def noul_question_ids(questions: list[dict]) -> tuple[str, ...]:
    return tuple(q["id"] for q in questions if is_global_qc(q) and q.get("type") == "noul")


def quality_expected_keys(questions: list[dict]) -> tuple[str, ...]:
    return tuple(q["id"] for q in questions if is_global_qc(q))


def question_applies(question: dict, row: dict) -> bool:
    exclude = question.get("exclude") or {}
    if row["id"] in _as_list(exclude.get("ids")):
        return False
    if row.get("specId") in _as_list(exclude.get("specIds")):
        return False
    applies = question.get("applies") or {}
    if applies.get("all"):
        return True
    filters = False
    if applies.get("ids"):
        filters = True
        if row["id"] not in _as_list(applies.get("ids")):
            return False
    if applies.get("specIds"):
        filters = True
        if row.get("specId") not in _as_list(applies.get("specIds")):
            return False
    if applies.get("kinds"):
        filters = True
        if row.get("kind") not in _as_list(applies.get("kinds")):
            return False
    if applies.get("requirementTypes"):
        filters = True
        if row.get("requirementType") not in _as_list(applies.get("requirementTypes")):
            return False
    if applies.get("tags"):
        filters = True
        row_tags = {str(tag) for tag in (row.get("reqTags") or [])}
        if not row_tags.intersection({str(tag) for tag in _as_list(applies.get("tags"))}):
            return False
    return filters


def expected_for(question: dict, row_id: str):
    expected = question.get("expected")
    if isinstance(expected, dict):
        return expected.get(row_id)
    return expected


def targeted_laya_by_requirement(rows: list[dict], questions: list[dict]) -> dict[str, dict]:
    by_id: dict[str, dict] = {}
    for question in questions:
        if question.get("type") not in LAYA_TYPES or is_global_qc(question):
            continue
        for row in rows:
            if not question_applies(question, row):
                continue
            by_id.setdefault(row["id"], {})[question["id"]] = laya_payload(question)
    return by_id


def targeted_expected_by_requirement(rows: list[dict], questions: list[dict]) -> dict[str, dict]:
    by_id: dict[str, dict] = {}
    for question in questions:
        if question.get("type") not in LAYA_TYPES or is_global_qc(question):
            continue
        for row in rows:
            if not question_applies(question, row):
                continue
            value = expected_for(question, row["id"])
            if value is None:
                continue
            by_id.setdefault(row["id"], {})[question["id"]] = value
    return by_id


def defect_criteria_from_questions(questions: list[dict]) -> dict[str, str]:
    for question in questions:
        if question["id"] == "primary_defect":
            criteria = question.get("criteria") or {}
            return {str(key): str(value) for key, value in criteria.items() if key != "none"}
    return {}


def _haystack(row: dict, check: dict) -> str:
    target = check.get("in", "state")
    if target == "state":
        return row["state"]
    raise ValueError(f"Unsupported check.in {target!r}")


def run_question_checks(row: dict, question: dict) -> list[str]:
    errors = []
    for index, check in enumerate(question.get("checks") or []):
        if not isinstance(check, dict) or "text" not in check:
            errors.append(f"{question['id']}@{row['id']} checks[{index}]: needs type and text")
            continue
        kind = check.get("type")
        needle = str(check["text"])
        found = needle.lower() in _haystack(row, check).lower()
        if kind == "contains" and not found:
            errors.append(f"{question['id']}@{row['id']} checks[{index}]: expected to contain {needle!r}")
        elif kind == "notContains" and found:
            errors.append(f"{question['id']}@{row['id']} checks[{index}]: must not contain {needle!r}")
        elif kind not in {"contains", "notContains"}:
            errors.append(f"{question['id']}@{row['id']} checks[{index}]: unknown type {kind!r}")
    return errors


def validate_questions(rows: list[dict], questions_dir: Path) -> list[str]:
    questions = load_question_documents(questions_dir)
    errors: list[str] = []
    global_ids = [q["id"] for q in questions if is_global_qc(q)]
    if not global_ids:
        errors.append("no global QC questions (applies.all + noul/choice/score)")
    seen = set()
    rows_by_id = {row["id"]: row for row in rows}
    for question in questions:
        qid = question["id"]
        if qid in seen:
            errors.append(f"duplicate question id {qid}")
        seen.add(qid)
        applies = question.get("applies") or {}
        if not applies.get("all") and not any(
            applies.get(key) for key in ("ids", "specIds", "kinds", "requirementTypes", "tags")
        ):
            errors.append(f"{qid}: applies must set all: true or at least one selector")
        for key in ("ids", "specIds"):
            for ref in _as_list((applies.get(key) or []) + _as_list((question.get("exclude") or {}).get(key))):
                if key == "ids" and ref not in rows_by_id:
                    errors.append(f"{qid}: applies/exclude id {ref} not found in catalogs")
        if question.get("type") == CHECK_TYPE and not question.get("checks"):
            errors.append(f"{qid}: check questions need checks[]")
        if question.get("type") in LAYA_TYPES and not is_global_qc(question):
            targets = [row for row in rows if question_applies(question, row)]
            if not targets:
                errors.append(f"{qid}: targeted Laya question matches no requirements")
            for row in targets:
                if expected_for(question, row["id"]) is None:
                    errors.append(f"{qid}: missing expected for {row['id']}")
        for row in rows:
            if question_applies(question, row) and question.get("checks"):
                errors.extend(run_question_checks(row, question))
    return errors


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Validate quality question YAML against catalogs.")
    add_project_args(parser)
    args = parser.parse_args(argv)
    paths = paths_from_args(args)
    vocabulary = load_vocabulary(paths.vocabulary_path)
    rows = iter_requirement_rows(paths.requirements_root, vocabulary=vocabulary)
    errors = validate_questions(rows, paths.questions_dir)
    if errors:
        for err in errors:
            print(f"FAIL {err}")
        return 1
    questions = load_question_documents(paths.questions_dir)
    targeted = sum(1 for q in questions if q.get("type") in LAYA_TYPES and not is_global_qc(q))
    checks = sum(1 for q in questions if q.get("type") == CHECK_TYPE)
    print(
        f"{len(global_laya_questions(questions))} global QC questions, "
        f"{targeted} targeted Laya question(s), {checks} check question(s)."
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
