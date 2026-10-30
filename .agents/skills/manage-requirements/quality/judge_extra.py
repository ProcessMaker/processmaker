"""Run targeted Laya questions (applies.ids / kinds / tags, not global QC)."""

from __future__ import annotations

import argparse
import json
from pathlib import Path

from extract_requirement_states import iter_requirement_rows
from load_questions import (
    global_laya_questions,
    load_question_documents,
    targeted_expected_by_requirement,
    targeted_laya_by_requirement,
    validate_questions,
)
from project import add_project_args, load_vocabulary, paths_from_args


def _matches(expected, answer: dict) -> bool:
    if "noul" in answer and isinstance(expected, bool):
        return (float(answer["noul"]) >= 0.5) is expected
    if "choice" in answer:
        return answer["choice"] == expected
    if "score" in answer and isinstance(expected, (int, float)):
        return int(round(float(answer["score"]))) == int(expected)
    return False


def judge_extra_answers(
    answers_by_id: dict[str, dict],
    expected_by_id: dict[str, dict],
    reserved_ids: set[str] | None = None,
) -> list[str]:
    errors = []
    reserved = reserved_ids or set()
    for row_id, expected in expected_by_id.items():
        answers = answers_by_id.get(row_id) or {}
        for qid, value in expected.items():
            if qid in reserved:
                errors.append(f"{row_id}: extra id {qid} collides with global QC")
                continue
            if not _matches(value, answers.get(qid) or {}):
                errors.append(f"{row_id} extra.{qid}: expected {value!r}, got {answers.get(qid)!r}")
    return errors


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Judge targeted per-requirement Laya questions.")
    add_project_args(parser)
    parser.add_argument("--report", type=Path)
    args = parser.parse_args(argv)

    paths = paths_from_args(args)
    questions = load_question_documents(paths.questions_dir)
    quality_questions = global_laya_questions(questions)
    vocabulary = load_vocabulary(paths.vocabulary_path)
    rows = iter_requirement_rows(
        paths.requirements_root,
        vocabulary=vocabulary,
        questions=quality_questions,
    )
    errors = validate_questions(rows, paths.questions_dir)
    if errors:
        for err in errors:
            print(f"FAIL {err}")
        return 1

    extra_questions = targeted_laya_by_requirement(rows, questions)
    extra_expected = targeted_expected_by_requirement(rows, questions)
    if not extra_questions:
        print("No targeted Laya questions.")
        return 0

    from laya import Router

    rows_by_id = {row["id"]: row for row in rows}
    router = Router()
    answers_by_id = {}
    for row_id, qset in extra_questions.items():
        result = router.predict(rows_by_id[row_id]["state"], qset, model="english")
        answers_by_id[row_id] = result["answers"]

    if args.report:
        args.report.parent.mkdir(parents=True, exist_ok=True)
        args.report.write_text(json.dumps(answers_by_id, indent=2, default=str) + "\n", encoding="utf-8")

    mismatches = judge_extra_answers(answers_by_id, extra_expected, set(quality_questions))
    if mismatches:
        for err in mismatches:
            print(f"FAIL {err}")
        return 1
    print(f"{len(extra_questions)} targeted Laya requirement(s) matched expected labels.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
