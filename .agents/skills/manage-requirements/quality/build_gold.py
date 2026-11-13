"""Generate targeted-extra JSONL from catalogs + question YAML (optional Laya input)."""

from __future__ import annotations

import argparse
import json
from pathlib import Path

from extract_requirement_states import iter_requirement_rows, to_jsonl_record
from load_questions import (
    global_laya_questions,
    load_question_documents,
    targeted_expected_by_requirement,
    targeted_laya_by_requirement,
    validate_questions,
)
from project import add_project_args, load_vocabulary, paths_from_args


def write_jsonl(path: Path, records: list[dict]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8") as handle:
        for record in records:
            handle.write(json.dumps(record, ensure_ascii=False) + "\n")


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser()
    add_project_args(parser)
    parser.add_argument("--extra-out", type=Path, help="Default: <questions>/extra.jsonl")
    args = parser.parse_args(argv)

    paths = paths_from_args(args)
    extra_out = args.extra_out or (paths.questions_dir / "extra.jsonl")

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

    rows_by_id = {row["id"]: row for row in rows}
    extra_questions = targeted_laya_by_requirement(rows, questions)
    extra_expected = targeted_expected_by_requirement(rows, questions)
    extra_records = [
        to_jsonl_record(rows_by_id[key], extra_expected[key], questions=extra_questions[key])
        for key in extra_questions
        if key in extra_expected
    ]

    write_jsonl(extra_out, extra_records)
    print(f"Wrote {len(extra_records)} extra-question rows to {extra_out}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
