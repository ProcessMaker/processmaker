from __future__ import annotations

import json
import shutil
import sys
import tempfile
import unittest
from pathlib import Path

import yaml

DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(DIR))

from extract_requirement_states import (
    iter_requirement_rows,
    requirement_has_stub_ac,
    requirement_line_numbers,
)
from judge_catalog import (
    format_failure_detail,
    format_quality_report,
    heuristic_answers,
    report_quality_failures,
    snapshot_from_rows,
)
from judge_extra import judge_extra_answers
from load_questions import (
    defect_criteria_from_questions,
    is_global_qc,
    load_question_documents,
    noul_question_ids,
    question_applies,
    targeted_laya_by_requirement,
    validate_questions,
)
from project import ProjectPaths, quality_packs_for_tests


def write_yaml(path: Path, data: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(yaml.safe_dump(data, sort_keys=False), encoding="utf-8")


def pack_questions(pack: ProjectPaths) -> list[dict]:
    return load_question_documents(pack.questions_dir)


def pack_noul_ids(pack: ProjectPaths) -> tuple[str, ...]:
    return noul_question_ids(pack_questions(pack))


def install_global_questions(
    questions_dir: Path,
    pack: ProjectPaths,
    extra: dict[str, dict] | None = None,
) -> None:
    """Copy applies.all question YAML from the project quality pack into the fixture."""
    questions_dir.mkdir(parents=True, exist_ok=True)
    copied = 0
    for question in pack_questions(pack):
        if not is_global_qc(question):
            continue
        src = Path(question["path"])
        shutil.copy2(src, questions_dir / src.name)
        copied += 1
    if copied == 0:
        raise RuntimeError(
            f"No global QC questions (applies.all) in {pack.questions_dir}"
        )
    for name, payload in (extra or {}).items():
        write_yaml(questions_dir / f"{name}.yaml", payload)


def sample_catalog(*, stub: bool = False) -> dict:
    then = "X" if stub else "The matcher writes matchMode Auto or Manual"
    given = (
        "The feature is available in an active sample case"
        if stub
        else "A course with a resolved institution is match-eligible"
    )
    when = "The behavior is exercised" if stub else "Matching runs"
    return {
        "specId": "sample-feature",
        "kind": "component",
        "title": "Sample feature",
        "requirements": [
            {
                "id": "FR-001",
                "requirementType": "Functional",
                "title": "X",
                "statement": "Matching records a concrete matchMode.",
                "priority": "High",
                "status": "Draft",
                "acceptanceCriteria": [
                    {"given": given, "when": when, "then": then},
                ],
            },
            {
                "id": "FR-002",
                "requirementType": "Functional",
                "title": "Observable outcome",
                "statement": "Save persists the chosen articulation type.",
                "priority": "High",
                "status": "Draft",
                "acceptanceCriteria": [
                    {
                        "given": "A course is open in evaluation",
                        "when": "The reviewer saves Direct transferable equivalency",
                        "then": "articulation.type is Direct and acronym is T-EQ",
                    }
                ],
            },
        ],
    }


def build_fixture(
    tmp: str | Path,
    pack: ProjectPaths,
    *,
    stub: bool = False,
    extra_questions: dict[str, dict] | None = None,
) -> tuple[Path, Path, list[dict]]:
    root = Path(tmp)
    req_root = root / "requirements"
    questions_dir = root / "quality"
    write_yaml(
        req_root / "components" / "sample-feature" / "requirements.yaml",
        sample_catalog(stub=stub),
    )
    install_global_questions(questions_dir, pack, extra_questions)
    rows = iter_requirement_rows(req_root, repo_root=root)
    return req_root, questions_dir, rows


class ProjectPackTestCase(unittest.TestCase):
    """Harness tests that need a project's quality/*.yaml as the question source."""

    def quality_packs(self) -> list[ProjectPaths]:
        return quality_packs_for_tests()


class ExtractTests(ProjectPackTestCase):
    def test_iter_rows_from_catalog(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as tmp:
                    _, _, rows = build_fixture(tmp, pack)
                    self.assertEqual(len(rows), 2)
                    self.assertEqual(
                        {row["id"] for row in rows},
                        {"sample-feature#FR-001", "sample-feature#FR-002"},
                    )
                    self.assertTrue(all(row["sourceLine"] for row in rows))

    def test_stub_ac_detection(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as good_tmp, tempfile.TemporaryDirectory() as bad_tmp:
                    _, _, good_rows = build_fixture(good_tmp, pack, stub=False)
                    _, _, bad_rows = build_fixture(bad_tmp, pack, stub=True)
                    by_good = {row["id"]: row for row in good_rows}
                    by_bad = {row["id"]: row for row in bad_rows}
                    self.assertFalse(by_good["sample-feature#FR-001"]["stub_ac"])
                    self.assertTrue(by_bad["sample-feature#FR-001"]["stub_ac"])
                    self.assertTrue(
                        requirement_has_stub_ac(
                            {
                                "title": "X",
                                "acceptanceCriteria": [
                                    {
                                        "given": "The feature is available in an active sample case",
                                        "when": "The behavior is exercised",
                                        "then": "X",
                                    }
                                ],
                            }
                        )
                    )


class QualityGateTests(ProjectPackTestCase):
    def test_heuristic_marks_stub_ac(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                rows = [
                    {"id": "good#FR-001", "stub_ac": False},
                    {"id": "bad#FR-001", "stub_ac": True},
                ]
                answers = {row["id"]: heuristic_answers(row) for row in rows}
                snap = snapshot_from_rows(
                    rows, answers, "heuristic", pack_noul_ids(pack)
                )
                self.assertEqual(snap["failedIds"], ["bad#FR-001"])
                self.assertEqual(snap["lowConfidenceCount"], 0)

    def test_report_includes_rich_failure_location(self):
        current = {
            "requirementCount": 2,
            "failedIds": ["sample-feature#FR-001", "other-feature#FR-001"],
            "failures": [
                {
                    "id": "sample-feature#FR-001",
                    "requirementId": "FR-001",
                    "title": "Sample rows",
                    "sourcePath": "requirements/components/sample-feature/requirements.yaml",
                    "sourceLine": 12,
                    "reasons": ["testable", "verifiable"],
                    "primary_defect": "stub_ac",
                },
                {
                    "id": "other-feature#FR-001",
                    "requirementId": "FR-001",
                    "title": "Other entry",
                    "sourcePath": "requirements/screens/other-feature/requirements.yaml",
                    "sourceLine": 10,
                    "reasons": ["testable", "verifiable"],
                    "primary_defect": "stub_ac",
                },
            ],
            "lowConfidenceCount": 0,
        }
        report = "\n".join(format_quality_report(current, color=False))
        self.assertIn("Requirements analyzed: 2", report)
        self.assertIn("Passed: 0", report)
        self.assertIn("Failed: 2", report)
        self.assertIn(
            "✖ requirements/components/sample-feature/requirements.yaml#12 FR-001: Sample rows [FAILED]",
            report,
        )
        self.assertIn("Failed checks: [testable, verifiable]", report)
        self.assertIn("Reason: stub acceptance criteria", report)

    def test_clean_catalog_has_no_report(self):
        current = {
            "requirementCount": 1,
            "failedIds": [],
            "failures": [],
            "lowConfidenceCount": 0,
        }
        self.assertEqual(report_quality_failures(current), [])

    def test_format_failure_detail_includes_quality_ids(self):
        lines = format_failure_detail(
            {
                "id": "sample-feature#FR-002",
                "requirementId": "FR-002",
                "title": "Observable outcome",
                "sourcePath": "requirements/components/sample-feature/requirements.yaml",
                "sourceLine": 28,
                "reasons": ["verifiable", "testable"],
                "primary_defect": "stub_ac",
            },
            color=False,
        )
        self.assertEqual(
            lines,
            [
                "✖ requirements/components/sample-feature/requirements.yaml#28 FR-002: Observable outcome [FAILED]",
                "Failed checks: [verifiable, testable]",
                "Reason: stub acceptance criteria",
            ],
        )

    def test_source_line_matches_yaml_id_entry(self):
        text = (
            "requirements:\n"
            "  - id: FR-001\n"
            "    title: First\n"
            "  - id: FR-002\n"
            "    title: Second\n"
        )
        lines = requirement_line_numbers(text)
        self.assertEqual(lines["FR-001"], 2)
        self.assertEqual(lines["FR-002"], 4)

    def test_fixture_stub_failures_appear_in_report(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as tmp:
                    _, questions_dir, rows = build_fixture(tmp, pack, stub=True)
                    questions = load_question_documents(questions_dir)
                    answers = {row["id"]: heuristic_answers(row) for row in rows}
                    current = snapshot_from_rows(
                        rows, answers, "heuristic", noul_question_ids(questions)
                    )
                    report = report_quality_failures(
                        current, defect_info=defect_criteria_from_questions(questions)
                    )
                    self.assertTrue(report)
                    self.assertTrue(
                        any("stub acceptance criteria" in line for line in report)
                    )
                    self.assertIn("sample-feature#FR-001", current["failedIds"])
                    self.assertNotIn("sample-feature#FR-002", current["failedIds"])


class QuestionYamlTests(ProjectPackTestCase):
    def test_quality_packs_have_global_qc(self):
        packs = self.quality_packs()
        self.assertTrue(packs)
        for pack in packs:
            with self.subTest(project=pack.name):
                questions = pack_questions(pack)
                globals_ = [q for q in questions if is_global_qc(q)]
                self.assertTrue(pack.questions_dir.is_dir())
                self.assertGreaterEqual(len(globals_), 1)
                self.assertTrue(pack_noul_ids(pack))

    def test_questions_validate_against_fixture_catalog(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as tmp:
                    _, questions_dir, rows = build_fixture(
                        tmp,
                        pack,
                        extra_questions={
                            "uses_match_mode": {
                                "id": "uses_match_mode",
                                "type": "check",
                                "applies": {"ids": ["sample-feature#FR-001"]},
                                "checks": [{"type": "contains", "text": "matchMode"}],
                            },
                            "save_writes_type": {
                                "id": "save_writes_type",
                                "type": "noul",
                                "applies": {"ids": ["sample-feature#FR-002"]},
                                "instructions": "Does save write articulation.type?",
                                "expected": True,
                            },
                        },
                    )
                    self.assertEqual(validate_questions(rows, questions_dir), [])
                    questions = load_question_documents(questions_dir)
                    extra = targeted_laya_by_requirement(rows, questions)
                    self.assertEqual(set(extra), {"sample-feature#FR-002"})
                    self.assertIn("save_writes_type", extra["sample-feature#FR-002"])

    def test_global_applies_to_every_requirement(self):
        row = {
            "id": "x#FR-001",
            "specId": "x",
            "kind": "script",
            "requirementType": "NonFunctional",
            "reqTags": [],
        }
        self.assertTrue(question_applies({"applies": {"all": True}}, row))
        self.assertFalse(question_applies({"applies": {"ids": ["other#FR-001"]}}, row))
        self.assertTrue(question_applies({"applies": {"kinds": ["script"]}}, row))
        self.assertFalse(question_applies({"applies": {"kinds": ["screen"]}}, row))

    def test_unknown_applies_id_fails(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as tmp:
                    _, questions_dir, rows = build_fixture(tmp, pack)
                    write_yaml(
                        questions_dir / "scoped.yaml",
                        {
                            "id": "scoped",
                            "type": "noul",
                            "applies": {"ids": ["sample-feature#FR-999"]},
                            "instructions": "x",
                            "expected": True,
                        },
                    )
                    errors = validate_questions(rows, questions_dir)
                    self.assertTrue(any("FR-999" in err for err in errors))

    def test_check_contains_missing_text_fails(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as tmp:
                    _, questions_dir, rows = build_fixture(tmp, pack)
                    write_yaml(
                        questions_dir / "bad_check.yaml",
                        {
                            "id": "bad_check",
                            "type": "check",
                            "applies": {"ids": ["sample-feature#FR-001"]},
                            "checks": [{"type": "contains", "text": "ace_id"}],
                        },
                    )
                    errors = validate_questions(rows, questions_dir)
                    self.assertTrue(any("expected to contain" in err for err in errors))

    def test_extra_answer_mismatch(self):
        errors = judge_extra_answers(
            {"sample-feature#FR-002": {"save_writes_type": {"noul": 0.1}}},
            {"sample-feature#FR-002": {"save_writes_type": True}},
        )
        self.assertEqual(len(errors), 1)

    def test_extra_cannot_reuse_global_ids(self):
        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                reserved = pack_noul_ids(pack)[0]
                errors = judge_extra_answers(
                    {"x#FR-001": {reserved: {"noul": 0.9}}},
                    {"x#FR-001": {reserved: True}},
                    reserved_ids={reserved},
                )
                self.assertTrue(any("collides" in err for err in errors))


class GoldBuildTests(ProjectPackTestCase):
    def test_build_gold_writes_extra_jsonl(self):
        import build_gold

        for pack in self.quality_packs():
            with self.subTest(project=pack.name):
                with tempfile.TemporaryDirectory() as tmp:
                    req_root, questions_dir, _ = build_fixture(
                        tmp,
                        pack,
                        extra_questions={
                            "save_writes_type": {
                                "id": "save_writes_type",
                                "type": "noul",
                                "applies": {"ids": ["sample-feature#FR-002"]},
                                "instructions": "Does save write articulation.type?",
                                "expected": True,
                            },
                        },
                    )
                    extra = Path(tmp) / "extra.jsonl"
                    self.assertEqual(
                        build_gold.main(
                            [
                                "--root",
                                str(req_root),
                                "--questions",
                                str(questions_dir),
                                "--extra-out",
                                str(extra),
                            ]
                        ),
                        0,
                    )
                    extra_lines = [
                        json.loads(line)
                        for line in extra.read_text(encoding="utf-8").splitlines()
                        if line
                    ]
                    self.assertEqual(len(extra_lines), 1)
                    self.assertIn("save_writes_type", extra_lines[0]["questions"])
                    for global_id in pack_noul_ids(pack):
                        self.assertNotIn(global_id, extra_lines[0]["questions"])


if __name__ == "__main__":
    unittest.main()
