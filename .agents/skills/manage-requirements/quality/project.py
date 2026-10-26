"""Resolve per-project requirements + quality-question paths under ai-docs/."""

from __future__ import annotations

import argparse
import os
from dataclasses import dataclass
from pathlib import Path

QUALITY_PKG = Path(__file__).resolve().parent
# quality/ -> manage-requirements/ -> skills/ -> .agents/ -> <repo>
REPO_ROOT = QUALITY_PKG.parents[3]


@dataclass(frozen=True)
class ProjectPaths:
    name: str | None
    requirements_root: Path
    questions_dir: Path
    vocabulary_path: Path


def resolve_project(name: str, repo_root: Path | None = None) -> ProjectPaths:
    root = repo_root or REPO_ROOT
    questions_dir = root / "ai-docs" / name / "quality"
    return ProjectPaths(
        name=name,
        requirements_root=root / "ai-docs" / name / "requirements",
        questions_dir=questions_dir,
        vocabulary_path=questions_dir / "vocabulary.md",
    )


def discover_quality_projects(repo_root: Path | None = None) -> list[str]:
    """Return ai-docs/<name> folders that contain quality/*.yaml."""
    docs = (repo_root or REPO_ROOT) / "ai-docs"
    if not docs.is_dir():
        return []
    names: list[str] = []
    for path in sorted(docs.iterdir()):
        if not path.is_dir() or path.name.startswith("."):
            continue
        quality = path / "quality"
        if quality.is_dir() and any(quality.glob("*.yaml")):
            names.append(path.name)
    return names


def quality_packs_for_tests(
    project: str | None = None,
    *,
    repo_root: Path | None = None,
    environ: dict[str, str] | None = None,
) -> list[ProjectPaths]:
    """
    Quality packs to exercise in harness unit tests.

    Resolution order:
    1. Explicit ``project`` argument
    2. ``REQUIREMENTS_PROJECT`` env
    3. Every discovered ``ai-docs/*/quality`` pack
    """
    env = environ if environ is not None else os.environ
    chosen = (project or env.get("REQUIREMENTS_PROJECT") or "").strip()
    if chosen:
        paths = resolve_project(chosen, repo_root=repo_root)
        if not paths.questions_dir.is_dir():
            raise FileNotFoundError(
                f"No quality directory for project {chosen!r}: {paths.questions_dir}"
            )
        if not any(paths.questions_dir.glob("*.yaml")):
            raise FileNotFoundError(
                f"No quality/*.yaml for project {chosen!r}: {paths.questions_dir}"
            )
        return [paths]

    names = discover_quality_projects(repo_root=repo_root)
    if not names:
        raise FileNotFoundError(
            "No ai-docs/<project>/quality/*.yaml packs found; "
            "add questions or set REQUIREMENTS_PROJECT"
        )
    return [resolve_project(name, repo_root=repo_root) for name in names]


def load_vocabulary(path: Path | None) -> str:
    if path is None or not path.is_file():
        return ""
    return path.read_text(encoding="utf-8").strip()


def add_project_args(parser: argparse.ArgumentParser) -> None:
    parser.add_argument(
        "--project",
        help="Project folder under ai-docs/ (sets --root/--questions/--vocabulary)",
    )
    parser.add_argument("--root", type=Path, help="Tree of requirements.yaml catalogs")
    parser.add_argument(
        "--questions",
        type=Path,
        help="Directory of quality question *.yaml files",
    )
    parser.add_argument(
        "--vocabulary",
        type=Path,
        help="Optional vocabulary markdown prepended to each Laya state",
    )


def paths_from_args(args: argparse.Namespace) -> ProjectPaths:
    if getattr(args, "project", None):
        paths = resolve_project(args.project)
        root = Path(args.root) if getattr(args, "root", None) else paths.requirements_root
        questions = (
            Path(args.questions) if getattr(args, "questions", None) else paths.questions_dir
        )
        vocabulary = (
            Path(args.vocabulary) if getattr(args, "vocabulary", None) else paths.vocabulary_path
        )
        return ProjectPaths(
            name=paths.name,
            requirements_root=root,
            questions_dir=questions,
            vocabulary_path=vocabulary,
        )

    root = getattr(args, "root", None)
    questions = getattr(args, "questions", None)
    if not root or not questions:
        raise SystemExit("Pass --project NAME, or both --root and --questions")

    root = Path(root)
    questions = Path(questions)
    vocabulary = Path(args.vocabulary) if getattr(args, "vocabulary", None) else (
        questions / "vocabulary.md"
    )
    return ProjectPaths(
        name=None,
        requirements_root=root,
        questions_dir=questions,
        vocabulary_path=vocabulary,
    )
