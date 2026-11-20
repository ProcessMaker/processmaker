# Requirement quality harness

Cross-project Python tools for FR/NFR quality (schema companion to `scripts/validate-requirements.js`).

Project-specific text stays under `ai-docs/<project>/quality/`:

| Path | Role |
| --- | --- |
| `*.yaml` | Global QC (`applies.all`) and targeted extras |
| `vocabulary.md` | Optional domain terms prepended to each Laya state |

```bash
# Heuristic gate (any project)
python3 judge_catalog.py --project college-v2 --source heuristic

# Or explicit paths
python3 judge_catalog.py \
  --root ai-docs/military/requirements \
  --questions ai-docs/military/quality \
  --source heuristic

# Unit tests: synthetic catalogs + global QC from ai-docs/<project>/quality
# Discovers every pack with quality/*.yaml, or pin one:
#   REQUIREMENTS_PROJECT=military python3 -m unittest discover -s . -p 'test_*.py'
python3 -m unittest discover -s . -p 'test_*.py'

# Laya (optional)
pip install -r requirements.txt
REQUIREMENTS_PROJECT=college-v2 LAYA_SOURCE=laya ./run_laya_eval.sh
```
