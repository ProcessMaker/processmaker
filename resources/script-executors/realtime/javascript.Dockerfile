# syntax=docker/dockerfile:1
# Realtime Node.js executor: fresh container per request via `docker run --rm -i`.
#
# The service pipes one JSON payload on stdin. bootstrap.js runs the script,
# prints the JSON result on stdout, and exits. The container is removed on exit.

FROM node:22-alpine

COPY <<'EOF' /bootstrap.js
#!/usr/bin/env node
/**
 * Read one JSON payload from stdin, run the script, print result, exit.
 *
 * Expected payload shape:
 *   { "script": "...", "data": {}, "config": {}, "env": {} }
 */

'use strict';

const fs = require('fs');

function writeError(error, { code = 0, file = '', line = 0, trace = '' } = {}) {
  process.stderr.write(
    JSON.stringify({ error, code, file, line, trace }) + '\n'
  );
}

/** Best-effort line/file from a Function/eval stack frame. */
function locationFromError(e) {
  const stack = typeof e.stack === 'string' ? e.stack : '';
  // new Function bodies show up as <anonymous>:LINE:COL
  const anon = stack.match(/<anonymous>:(\d+):(\d+)/);
  if (anon) {
    return { file: '<anonymous>', line: Number(anon[1]) || 0 };
  }
  return { file: '', line: 0 };
}

let raw = '';
try {
  raw = fs.readFileSync(0, 'utf8');
} catch (e) {
  process.stderr.write('No input on stdin\n');
  process.exit(1);
}

if (!raw || !raw.trim()) {
  process.stderr.write('No input on stdin\n');
  process.exit(1);
}

let payload;
try {
  payload = JSON.parse(raw.trim());
} catch (e) {
  writeError(`Invalid JSON: ${e.message}`);
  process.exit(1);
}

const script = payload.script;
if (script === undefined || script === null || script === '') {
  writeError('Missing "script" field');
  process.exit(1);
}

// Available to the evaluated script (mirrors the PHP/Python convention).
const data = payload.data ?? {};
const config = payload.config ?? {};

for (const [key, value] of Object.entries(payload.env ?? {})) {
  if (value === null || value === undefined) {
    process.env[key] = '';
  } else if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    process.env[key] = String(value);
  } else {
    process.env[key] = JSON.stringify(value);
  }
}

try {
  // new Function so the user script can `return` a value, like PHP eval.
  const fn = new Function('data', 'config', script);
  const result = fn(data, config);
  process.stdout.write(JSON.stringify(result) + '\n');
  process.exit(0);
} catch (e) {
  const { file, line: errLine } = locationFromError(e);
  writeError(e.message || String(e), {
    code: typeof e.code === 'number' ? e.code : 0,
    file,
    line: errLine,
    trace: typeof e.stack === 'string' ? e.stack : '',
  });
  process.exit(1);
}
EOF

CMD ["node", "/bootstrap.js"]
