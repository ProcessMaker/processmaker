"""Walk requirements.yaml catalogs and emit one Laya state per requirement."""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

import yaml

from project import REPO_ROOT, add_project_args, load_vocabulary, paths_from_args

PLACEHOLDER_GIVEN = "the feature is available"
PLACEHOLDER_WHEN = "the behavior is exercised"


def collect_yaml_files(root: Path) -> list[Path]:
    if root.is_file():
        return [root]
    return sorted(root.rglob("requirements.yaml"))


def _normalize(value: object) -> str:
    return " ".join(str(value or "").lower().split())


def is_stub_criterion(req: dict, criterion: dict) -> bool:
    given = str(criterion.get("given") or "")
    when = str(criterion.get("when") or "")
    then = _normalize(criterion.get("then"))
    title = _normalize(req.get("title"))
    placeholder = PLACEHOLDER_GIVEN in given.lower() and PLACEHOLDER_WHEN in when.lower()
    then_repeats_title = bool(then) and then == title
    return placeholder or then_repeats_title


def requirement_has_stub_ac(req: dict) -> bool:
    criteria = req.get("acceptanceCriteria") or []
    return any(is_stub_criterion(req, item) for item in criteria if isinstance(item, dict))


# Backward-compatible aliases.
is_tautological_criterion = is_stub_criterion
requirement_is_tautological = requirement_has_stub_ac


def compact_requirement(req: dict) -> dict:
    payload = {
        "id": req.get("id"),
        "requirementType": req.get("requirementType"),
        "title": req.get("title"),
        "statement": req.get("statement"),
        "priority": req.get("priority"),
        "status": req.get("status"),
    }
    if req.get("acceptanceCriteria"):
        payload["acceptanceCriteria"] = req["acceptanceCriteria"]
    if req.get("metrics"):
        payload["metrics"] = req["metrics"]
    if req.get("verificationMethod"):
        payload["verificationMethod"] = req["verificationMethod"]
    if req.get("category"):
        payload["category"] = req["category"]
    return payload


def build_state(catalog: dict, req: dict, vocabulary: str = "") -> str:
    body = yaml.safe_dump(compact_requirement(req), sort_keys=False, allow_unicode=True).strip()
    parts = []
    if vocabulary.strip():
        parts.append(vocabulary.strip())
        parts.append("")
    parts.extend(
        [
            "Requirement catalog:",
            f"specId: {catalog.get('specId')}",
            f"kind: {catalog.get('kind')}",
            f"title: {catalog.get('title')}",
            "",
            body,
            "",
        ]
    )
    return "\n".join(parts)


def requirement_key(spec_id: str, req_id: str) -> str:
    return f"{spec_id}#{req_id}"


def relative_repo_path(path: Path, repo_root: Path | None = None) -> str:
    root = repo_root or REPO_ROOT
    resolved = path.resolve()
    try:
        return resolved.relative_to(root.resolve()).as_posix()
    except ValueError:
        return resolved.as_posix()


def requirement_line_numbers(text: str) -> dict[str, int]:
    """Map requirement id -> 1-based line of the `- id:` entry."""
    lines: dict[str, int] = {}
    for index, line in enumerate(text.splitlines(), start=1):
        stripped = line.strip()
        if not stripped.startswith("- id:"):
            continue
        req_id = stripped.split(":", 1)[1].strip().strip("\"'")
        if req_id and req_id not in lines:
            lines[req_id] = index
    return lines


def iter_requirement_rows(
    root: Path,
    *,
    vocabulary: str = "",
    questions: dict | None = None,
    repo_root: Path | None = None,
) -> list[dict]:
    rows = []
    for yaml_path in collect_yaml_files(root):
        text = yaml_path.read_text(encoding="utf-8")
        catalog = yaml.safe_load(text)
        if not isinstance(catalog, dict):
            continue
        spec_id = catalog.get("specId")
        line_by_id = requirement_line_numbers(text)
        source_path = relative_repo_path(yaml_path, repo_root=repo_root)
        for req in catalog.get("requirements") or []:
            if not isinstance(req, dict) or not req.get("id"):
                continue
            req_id = str(req["id"])
            key = requirement_key(str(spec_id), req_id)
            tags = req.get("tags") or []
            if not isinstance(tags, list):
                tags = [tags]
            rows.append(
                {
                    "id": key,
                    "specId": spec_id,
                    "requirementId": req_id,
                    "title": str(req.get("title") or "").strip(),
                    "sourcePath": source_path,
                    "sourceLine": line_by_id.get(req_id),
                    "requirementType": req.get("requirementType"),
                    "kind": catalog.get("kind"),
                    "reqTags": [str(tag) for tag in tags],
                    "stub_ac": requirement_has_stub_ac(req),
                    "tautological": requirement_has_stub_ac(req),
                    "state": build_state(catalog, req, vocabulary=vocabulary),
                    "questions": questions if questions is not None else {},
                    "tags": [str(catalog.get("kind") or "unknown"), str(spec_id)],
                    "language": "en",
                    "model": "english",
                }
            )
    return rows


def to_jsonl_record(row: dict, expected: dict | None = None, questions: dict | None = None) -> dict:
    record = {
        "state": row["state"],
        "questions": questions if questions is not None else row["questions"],
        "tags": row["tags"] + ([row["id"]]),
        "language": row["language"],
        "model": row["model"],
    }
    if expected is not None:
        record["expected"] = expected
    return record


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Extract requirements as Laya states.")
    add_project_args(parser)
    parser.add_argument("--jsonl", type=Path, help="Write one JSON object per requirement")
    args = parser.parse_args(argv)

    paths = paths_from_args(args)
    vocabulary = load_vocabulary(paths.vocabulary_path)
    rows = iter_requirement_rows(paths.requirements_root, vocabulary=vocabulary)
    if args.jsonl:
        args.jsonl.parent.mkdir(parents=True, exist_ok=True)
        with args.jsonl.open("w", encoding="utf-8") as handle:
            for row in rows:
                handle.write(json.dumps(to_jsonl_record(row), ensure_ascii=False) + "\n")
    else:
        json.dump(
            [{"id": row["id"], "stub_ac": row["stub_ac"]} for row in rows],
            sys.stdout,
            indent=2,
        )
        sys.stdout.write("\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
