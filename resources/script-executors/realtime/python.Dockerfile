# syntax=docker/dockerfile:1
# Realtime Python executor: fresh container per request via `docker run --rm -i`.
#
# The service pipes one JSON payload on stdin. bootstrap.py execs the script,
# prints the JSON result on stdout, and exits. The container is removed on exit.

FROM python:3.12-alpine

COPY <<'EOF' /bootstrap.py
#!/usr/bin/env python3
"""Read one JSON payload from stdin, exec the script, print result, exit.

Expected payload shape:
  { "script": "...", "data": {}, "config": {}, "env": {} }
"""

from __future__ import annotations

import json
import os
import sys
import textwrap
import traceback


def write_error(
    error: str,
    *,
    code: int = 0,
    file: str = "",
    line: int = 0,
    trace: str = "",
) -> None:
    print(
        json.dumps(
            {
                "error": error,
                "code": code,
                "file": file,
                "line": line,
                "trace": trace,
            },
            ensure_ascii=False,
        ),
        file=sys.stderr,
        flush=True,
    )


def main() -> int:
    raw = sys.stdin.read()
    if not raw or not raw.strip():
        print("No input on stdin", file=sys.stderr)
        return 1

    try:
        payload = json.loads(raw.strip())
    except json.JSONDecodeError as e:
        write_error(f"Invalid JSON: {e}")
        return 1

    script = payload.get("script")
    if not script:
        write_error('Missing "script" field')
        return 1

    # Available to the evaluated script (mirrors the PHP convention).
    data = payload.get("data") or {}
    config = payload.get("config") or {}

    for key, value in (payload.get("env") or {}).items():
        if value is None or isinstance(value, (str, int, float, bool)):
            os.environ[str(key)] = "" if value is None else str(value)
        else:
            os.environ[str(key)] = json.dumps(value)

    # Wrap so the user script can `return` a value, like PHP eval.
    # Leading "def" line shifts user line numbers by +1; we subtract it when reporting.
    wrapped = "def __user_script__():\n" + textwrap.indent(script.strip(), "    ")
    namespace: dict = {"data": data, "config": config}

    try:
        compiled = compile(wrapped, "<script>", "exec")
        exec(compiled, namespace)
        result = namespace["__user_script__"]()
        print(json.dumps(result, ensure_ascii=False), flush=True)
        return 0
    except Exception as e:
        file = ""
        err_line = 0
        if isinstance(e, SyntaxError):
            file = e.filename or "<script>"
            err_line = max(0, (e.lineno or 0) - 1)
        else:
            frames = traceback.extract_tb(e.__traceback__)
            for frame in reversed(frames):
                if frame.filename == "<script>":
                    file = frame.filename
                    err_line = (
                        max(0, frame.lineno - 1)
                        if frame.name == "__user_script__"
                        else frame.lineno
                    )
                    break
            else:
                if frames:
                    file = frames[-1].filename
                    err_line = frames[-1].lineno
        write_error(
            str(e),
            file=file,
            line=err_line,
            trace=traceback.format_exc(),
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
EOF

CMD ["python", "-u", "/bootstrap.py"]
