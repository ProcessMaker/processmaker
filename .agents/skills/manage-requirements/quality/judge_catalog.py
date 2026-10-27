"""Judge requirements and fail when any global QC check fails."""

from __future__ import annotations

import argparse
import json
import os
import sys
from pathlib import Path

from extract_requirement_states import iter_requirement_rows
from load_questions import (
    defect_criteria_from_questions,
    global_laya_questions,
    load_question_documents,
    noul_question_ids,
)
from project import add_project_args, load_vocabulary, paths_from_args

NOUL_FAIL_THRESHOLD = 0.5
MAX_LOW_CONFIDENCE = 0
_ANSI_RED = "\033[31m"
_ANSI_RESET = "\033[0m"

DEFECT_LABELS = {
    "stub_ac": "stub acceptance criteria",
    "vague_then": "vague then/metric",
    "vocab_drift": "vocabulary drift",
    "missing_observable": "missing observable outcome",
    "unbounded": "unbounded / TBD scope",
}


def heuristic_answers(row: dict) -> dict:
    stub_ac = bool(row.get("stub_ac") if "stub_ac" in row else row.get("tautological"))
    yes = 0.95
    no = 0.05
    return {
        "clear": {"noul": yes, "answer_confidence": yes, "low_confidence": False},
        "verifiable": {
            "noul": no if stub_ac else yes,
            "answer_confidence": yes,
            "low_confidence": False,
        },
        "testable": {
            "noul": no if stub_ac else yes,
            "answer_confidence": yes,
            "low_confidence": False,
        },
        "consistent": {"noul": yes, "answer_confidence": yes, "low_confidence": False},
        "achievable": {"noul": yes, "answer_confidence": yes, "low_confidence": False},
        "primary_defect": {
            "choice": "stub_ac" if stub_ac else "none",
            "answer_confidence": yes,
            "low_confidence": False,
        },
    }


def noul_value(answer: dict) -> float:
    if "noul" in answer and isinstance(answer["noul"], (int, float)):
        return float(answer["noul"])
    choice = str(answer.get("choice") or "").upper()
    if choice == "A":
        return 1.0
    if choice == "B":
        return 0.0
    return 0.0


def failed_quality_reasons(
    answers: dict,
    noul_ids: tuple[str, ...],
    min_confidence: float = 0.45,
) -> list[str]:
    """Return every failed global QC question id (and low_confidence when gated)."""
    reasons = []
    for key in noul_ids:
        answer = answers.get(key) or {}
        confidence = float(answer.get("answer_confidence") or noul_value(answer) or 0.0)
        if answer.get("low_confidence") or confidence < min_confidence:
            reasons.append("low_confidence")
            continue
        if noul_value(answer) < NOUL_FAIL_THRESHOLD:
            reasons.append(key)
    seen = set()
    ordered = []
    for reason in reasons:
        if reason not in seen:
            seen.add(reason)
            ordered.append(reason)
    return ordered


def requirement_quality_failure(
    answers: dict,
    noul_ids: tuple[str, ...],
    min_confidence: float = 0.45,
) -> tuple[bool, str | None]:
    reasons = failed_quality_reasons(answers, noul_ids, min_confidence)
    if not reasons:
        return False, None
    return True, reasons[0]


def primary_defect_of(answers: dict) -> str | None:
    defect = answers.get("primary_defect") or {}
    choice = defect.get("choice")
    return str(choice) if choice else None


def defect_label(primary_defect: str | None) -> str | None:
    if not primary_defect or primary_defect == "none":
        return None
    return DEFECT_LABELS.get(primary_defect, primary_defect.replace("_", " "))


def failure_reason_text(primary_defect: str | None) -> str:
    return defect_label(primary_defect) or "quality check failed"


def use_color(stream=None) -> bool:
    force = (os.environ.get("FORCE_COLOR") or "").strip().lower()
    if force in {"1", "true", "yes", "on"}:
        return True
    if force in {"0", "false", "no", "off"}:
        return False
    if os.environ.get("NO_COLOR"):
        return False
    target = stream if stream is not None else sys.stdout
    return bool(getattr(target, "isatty", lambda: False)())


def failed_badge(color: bool | None = None) -> str:
    if color is None:
        color = use_color()
    if color:
        return f"{_ANSI_RED}[FAILED]{_ANSI_RESET}"
    return "[FAILED]"


def format_failure_heading(item: dict, *, color: bool | None = None) -> str:
    """Build: path#line FR-ID: Title [FAILED] (falls back to catalog#id)."""
    source_path = item.get("sourcePath")
    source_line = item.get("sourceLine")
    req_id = item.get("requirementId") or (
        str(item.get("id") or "").split("#", 1)[-1] if item.get("id") else "UNKNOWN"
    )
    title = str(item.get("title") or "").strip()
    if source_path and source_line:
        location = f"{source_path}#{source_line}"
    elif source_path:
        location = str(source_path)
    else:
        location = str(item.get("id") or req_id)
    badge = failed_badge(color)
    if title:
        return f"✖ {location} {req_id}: {title} {badge}"
    return f"✖ {location} {req_id} {badge}"


def format_failure_detail(
    item: dict | str,
    reasons: list[str] | None = None,
    primary_defect: str | None = None,
    *,
    color: bool | None = None,
    **kwargs,
) -> list[str]:
    if isinstance(item, str):
        item = {
            "id": item,
            "reasons": reasons or [],
            "primary_defect": primary_defect or kwargs.get("primary_defect"),
        }
    else:
        reasons = reasons if reasons is not None else item.get("reasons") or []
        if primary_defect is None:
            primary_defect = item.get("primary_defect")
    checks = ", ".join(reasons or []) if reasons else "unknown"
    return [
        format_failure_heading(item, color=color),
        f"Failed checks: [{checks}]",
        f"Reason: {failure_reason_text(primary_defect)}",
    ]


def snapshot_from_rows(
    rows: list[dict],
    answers_by_id: dict[str, dict],
    source: str,
    noul_ids: tuple[str, ...],
) -> dict:
    failures = []
    low_confidence = 0
    for row in rows:
        answers = answers_by_id[row["id"]]
        reasons = failed_quality_reasons(answers, noul_ids)
        if "low_confidence" in reasons:
            low_confidence += 1
        if reasons:
            failures.append(
                {
                    "id": row["id"],
                    "requirementId": row.get("requirementId"),
                    "title": row.get("title") or "",
                    "sourcePath": row.get("sourcePath"),
                    "sourceLine": row.get("sourceLine"),
                    "reasons": reasons,
                    "primary_defect": primary_defect_of(answers),
                }
            )
    failures.sort(key=lambda item: item["id"])
    return {
        "source": source,
        "failedIds": [item["id"] for item in failures],
        "failures": failures,
        "lowConfidenceCount": low_confidence,
        "requirementCount": len(rows),
    }


RULE = "─" * 60


def format_summary(current: dict) -> list[str]:
    total = int(current.get("requirementCount") or 0)
    failed = len(current.get("failures") or [])
    warnings = int(current.get("lowConfidenceCount") or 0)
    passed = max(total - failed, 0)
    return [
        RULE,
        f"Requirements analyzed: {total}",
        f"Passed: {passed}",
        f"Failed: {failed}",
        f"Warnings: {warnings}",
        RULE,
    ]


def format_quality_report(
    current: dict,
    defect_info: dict[str, str] | None = None,
    *,
    color: bool | None = None,
) -> list[str]:
    """Human-readable report lines for quality failures (empty when clean)."""
    failures = list(current.get("failures") or [])
    if not failures and current.get("lowConfidenceCount", 0) <= MAX_LOW_CONFIDENCE:
        return []

    lines = format_summary(current)
    lines.append("")
    for index, item in enumerate(failures):
        if index:
            lines.append("")
        lines.extend(format_failure_detail(item, defect_info=defect_info, color=color))
    lines.append("")
    return lines


def report_quality_failures(
    current: dict,
    defect_info: dict[str, str] | None = None,
    *,
    color: bool | None = None,
) -> list[str]:
    """Return error lines when the catalog has quality failures."""
    return format_quality_report(current, defect_info=defect_info, color=color)


def compare_snapshot(current: dict, committed: dict | None = None, **_kwargs) -> list[str]:
    del committed
    return report_quality_failures(current)


def predict_with_laya(rows: list[dict], min_confidence: float) -> dict[str, dict]:
    from laya import Router

    router = Router()
    answers_by_id = {}
    for row in rows:
        result = router.predict(
            row["state"],
            row["questions"],
            model="english",
            min_confidence=min_confidence,
        )
        answers_by_id[row["id"]] = result["answers"]
    return answers_by_id


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        description="Judge requirement quality; fails if any FR fails global QC."
    )
    add_project_args(parser)
    parser.add_argument("--source", choices=("heuristic", "laya"), default="heuristic")
    parser.add_argument("--min-confidence", type=float, default=0.45)
    parser.add_argument("--report-only", action="store_true")
    parser.add_argument("--report", type=Path)
    args = parser.parse_args(argv)

    paths = paths_from_args(args)
    questions = load_question_documents(paths.questions_dir)
    quality_questions = global_laya_questions(questions)
    noul_ids = noul_question_ids(questions)
    defect_info = defect_criteria_from_questions(questions)
    vocabulary = load_vocabulary(paths.vocabulary_path)
    rows = iter_requirement_rows(
        paths.requirements_root,
        vocabulary=vocabulary,
        questions=quality_questions,
    )

    if args.source == "laya":
        answers_by_id = predict_with_laya(rows, args.min_confidence)
        source = "laya"
    else:
        answers_by_id = {row["id"]: heuristic_answers(row) for row in rows}
        source = "heuristic"

    current = snapshot_from_rows(rows, answers_by_id, source, noul_ids)
    if args.report:
        args.report.parent.mkdir(parents=True, exist_ok=True)
        args.report.write_text(json.dumps(current, indent=2) + "\n", encoding="utf-8")

    report = report_quality_failures(current, defect_info=defect_info)
    if args.report_only:
        for line in report or format_summary(current):
            print(line)
        return 0

    if report:
        for line in report:
            print(line)
        return 1

    for line in format_summary(current):
        print(line)
    print("")
    print("All requirements passed global QC.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
