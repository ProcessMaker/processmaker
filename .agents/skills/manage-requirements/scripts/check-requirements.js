#!/usr/bin/env node
/**
 * Run schema validation + heuristic quality judging for requirement catalogs.
 *
 * Usage:
 *   node check-requirements.js
 *   node check-requirements.js college-v2
 *   node check-requirements.js --project military
 *
 * With no project arg, checks every ai-docs/<project>/requirements tree.
 * Project may also come from REQUIREMENTS_PROJECT when no CLI project is given.
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const SKILL_ROOT = path.resolve(__dirname, '..');
const REPO_ROOT = path.resolve(SKILL_ROOT, '../../..');
const AI_DOCS = path.join(REPO_ROOT, 'ai-docs');
const VALIDATE_JS = path.join(__dirname, 'validate-requirements.js');
const JUDGE_PY = path.join(SKILL_ROOT, 'quality', 'judge_catalog.py');

const colors = {
  reset: '\x1b[0m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  cyan: '\x1b[36m',
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

function parseArgs(argv) {
  let project = null;
  const rest = [];
  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    if (arg === '--project' || arg === '-p') {
      project = argv[i + 1] || null;
      i += 1;
      continue;
    }
    if (arg.startsWith('--project=')) {
      project = arg.slice('--project='.length) || null;
      continue;
    }
    if (arg.startsWith('-')) {
      rest.push(arg);
      continue;
    }
    if (project === null) {
      project = arg;
      continue;
    }
    rest.push(arg);
  }
  if (!project && process.env.REQUIREMENTS_PROJECT) {
    project = process.env.REQUIREMENTS_PROJECT;
  }
  return { project, rest };
}

function listRequirementProjects() {
  if (!fs.existsSync(AI_DOCS)) {
    return [];
  }
  return fs
    .readdirSync(AI_DOCS, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)
    .filter((name) => fs.existsSync(path.join(AI_DOCS, name, 'requirements')))
    .sort();
}

function hasQualityFixtures(project) {
  const qualityDir = path.join(AI_DOCS, project, 'quality');
  if (!fs.existsSync(qualityDir)) {
    return false;
  }
  return fs.readdirSync(qualityDir).some((name) => name.endsWith('.yaml'));
}

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: options.cwd || REPO_ROOT,
    env: { ...process.env, FORCE_COLOR: process.env.FORCE_COLOR || '1' },
    stdio: 'inherit',
  });
  if (result.error) {
    log(result.error.message, 'red');
    return 1;
  }
  return typeof result.status === 'number' ? result.status : 1;
}

function main(argv = process.argv.slice(2)) {
  const { project } = parseArgs(argv);
  const projects = project ? [project] : listRequirementProjects();

  if (projects.length === 0) {
    log('No ai-docs/*/requirements trees found.', 'red');
    return 1;
  }

  if (project) {
    const reqRoot = path.join(AI_DOCS, project, 'requirements');
    if (!fs.existsSync(reqRoot)) {
      log(`Requirements tree missing: ${path.relative(REPO_ROOT, reqRoot)}`, 'red');
      return 1;
    }
  }

  const schemaTargets = projects.map((name) => path.join(AI_DOCS, name, 'requirements'));
  log(`Schema: validating ${projects.join(', ')}...`, 'cyan');
  const schemaStatus = run(process.execPath, [VALIDATE_JS, ...schemaTargets]);
  if (schemaStatus !== 0) {
    return schemaStatus;
  }

  let qualityFailed = 0;
  for (const name of projects) {
    if (!hasQualityFixtures(name)) {
      log(`Quality: skip ${name} (no ai-docs/${name}/quality/*.yaml)`, 'yellow');
      continue;
    }
    log(`Quality: judging ${name} (heuristic)...`, 'cyan');
    const status = run('python3', [JUDGE_PY, '--project', name, '--source', 'heuristic']);
    if (status !== 0) {
      qualityFailed += 1;
    }
  }

  if (qualityFailed > 0) {
    log(`${qualityFailed} project quality gate(s) failed.`, 'red');
    return 1;
  }

  log('Schema and quality checks passed.', 'green');
  return 0;
}

module.exports = {
  hasQualityFixtures,
  listRequirementProjects,
  main,
  parseArgs,
};

if (require.main === module) {
  process.exit(main());
}
