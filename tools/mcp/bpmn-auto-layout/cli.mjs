#!/usr/bin/env node
import { layoutBpmn } from './layout.mjs';

async function readStdin() {
  const chunks = [];
  for await (const chunk of process.stdin) {
    chunks.push(chunk);
  }

  return Buffer.concat(chunks).toString('utf8');
}

function emitWarning(message) {
  process.stderr.write(`${JSON.stringify({ level: 'warning', message })}\n`);
}

async function main() {
  try {
    const input = await readStdin();
    const { xml, warnings } = await layoutBpmn(input);
    for (const message of warnings) {
      emitWarning(message);
    }
    process.stdout.write(xml);
    if (!xml.endsWith('\n')) {
      process.stdout.write('\n');
    }
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    process.stderr.write(`${JSON.stringify({ level: 'error', message })}\n`);
    process.exit(1);
  }
}

main();
