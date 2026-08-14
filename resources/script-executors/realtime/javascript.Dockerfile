# syntax=docker/dockerfile:1
# Experiment: warm Node.js container blocked on stdin
#
# Same idea as the PHP/Python variants: keep a container running with the
# interpreter already loaded and blocked on stdin. A client attaches, writes
# one JSON payload; bootstrap.js runs the script, prints the result, and exits.
#
# Usage:
#   ./warm.sh          # build + start warm container (foreground via docker wait)
#   ./run.sh           # attach, send payload.json, print result

FROM node:22-alpine

COPY <<'EOF' /bootstrap.js
#!/usr/bin/env node
/**
 * Wait for one JSON payload on stdin, run the script, print result, exit.
 *
 * Expected payload shape (see ../../payload.schema.json):
 *   { "script": "...", "data": {}, "config": {}, "env": {} }
 */

'use strict';

const readline = require('readline');

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

async function readLine() {
  const rl = readline.createInterface({
    input: process.stdin,
    crlfDelay: Infinity,
  });
  try {
    for await (const line of rl) {
      return line;
    }
    return null;
  } finally {
    rl.close();
  }
}

async function main() {
  const line = await readLine();
  if (line === null || line === undefined) {
    process.stderr.write('No input on stdin\n');
    return 1;
  }

  let payload;
  try {
    payload = JSON.parse(line.trim());
  } catch (e) {
    writeError(`Invalid JSON: ${e.message}`);
    return 1;
  }

  const script = payload.script;
  if (script === undefined || script === null || script === '') {
    writeError('Missing "script" field');
    return 1;
  }

  // Available to the evaluated script (mirrors the PHP/Python convention).
  const data = payload.data ?? {};
  const config = payload.config ?? {};

  // Apply per-request env before eval (warm containers can't use docker run -e).
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
    return 0;
  } catch (e) {
    const { file, line: errLine } = locationFromError(e);
    writeError(e.message || String(e), {
      code: typeof e.code === 'number' ? e.code : 0,
      file,
      line: errLine,
      trace: typeof e.stack === 'string' ? e.stack : '',
    });
    return 1;
  }
}

main().then((code) => process.exit(code));
EOF

CMD ["node", "/bootstrap.js"]
