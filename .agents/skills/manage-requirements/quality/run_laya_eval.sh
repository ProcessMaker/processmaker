#!/usr/bin/env bash
# Run requirement quality evals for a project (heuristic gate always; Laya when requested).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

PYTHON="${PYTHON:-python3}"
PROJECT="${REQUIREMENTS_PROJECT:-college-v2}"
SOURCE="${LAYA_SOURCE:-heuristic}"
REPORT_DIR="${REPORT_DIR:-$SCRIPT_DIR/report}"
mkdir -p "$REPORT_DIR"

PROJECT_ARGS=(--project "$PROJECT")

"$PYTHON" load_questions.py "${PROJECT_ARGS[@]}"
"$PYTHON" build_gold.py "${PROJECT_ARGS[@]}"
"$PYTHON" -m unittest test_quality_harness.py

"$PYTHON" judge_catalog.py "${PROJECT_ARGS[@]}" --source heuristic --report "$REPORT_DIR/catalog_snapshot.json"

if [[ "$SOURCE" != "laya" ]]; then
  echo "Skipping Laya catalog eval (LAYA_SOURCE=$SOURCE)."
  exit 0
fi

if ! "$PYTHON" -c "import laya" >/dev/null 2>&1; then
  echo "Laya is not installed. pip install -r requirements.txt" >&2
  exit 1
fi

"$PYTHON" judge_catalog.py "${PROJECT_ARGS[@]}" --source laya --report "$REPORT_DIR/catalog_snapshot_laya.json"
"$PYTHON" judge_extra.py "${PROJECT_ARGS[@]}" --report "$REPORT_DIR/extra-answers.json"
