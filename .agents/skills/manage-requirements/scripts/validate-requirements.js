#!/usr/bin/env node
/**
 * Validate requirements.yaml catalogs against the manage-requirements schemas.
 *
 * Usage:
 *   node .agents/skills/manage-requirements/scripts/validate-requirements.js [path...]
 *
 * Paths may be a requirements.yaml file, a feature directory, or a tree root.
 * Default: every ai-docs/<project>/requirements tree that exists.
 */

const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const Ajv = require('ajv/dist/2020');
const addFormats = require('ajv-formats');

const SKILL_ROOT = path.resolve(__dirname, '..');
const SCHEMA_DIR = path.join(SKILL_ROOT, 'schemas');
const REPO_ROOT = path.resolve(SKILL_ROOT, '../../..');
const AI_DOCS = path.join(REPO_ROOT, 'ai-docs');
const AC_FIELD_MIN_LENGTH = 8;

function defaultTargets() {
  if (!fs.existsSync(AI_DOCS)) {
    return [];
  }
  return fs
    .readdirSync(AI_DOCS, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => path.join(AI_DOCS, entry.name, 'requirements'))
    .filter((dir) => fs.existsSync(dir))
    .sort();
}

const DEFAULT_TARGET = path.join(REPO_ROOT, 'ai-docs/college-v2/requirements');
const PLACEHOLDER_GIVEN = /the feature is available/i;
const PLACEHOLDER_WHEN = /the behavior is exercised/i;

const colors = {
  reset: '\x1b[0m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  cyan: '\x1b[36m',
  bright: '\x1b[1m',
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

function loadJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function createAjv() {
  const ajv = new Ajv({
    allErrors: true,
    strict: false,
    validateSchema: true,
  });
  addFormats(ajv);

  const requirementSchema = loadJson(path.join(SCHEMA_DIR, 'requirement.schema.json'));
  const catalogSchema = loadJson(path.join(SCHEMA_DIR, 'requirements-catalog.schema.json'));

  ajv.addSchema(requirementSchema);
  ajv.addSchema(catalogSchema);

  return {
    ajv,
    validateCatalog: ajv.getSchema('requirements-catalog.schema.json'),
  };
}

function collectYamlFiles(targetPath) {
  const abs = path.resolve(targetPath);
  if (!fs.existsSync(abs)) {
    throw new Error(`Path not found: ${abs}`);
  }

  const stat = fs.statSync(abs);
  if (stat.isFile()) {
    if (!abs.endsWith('.yaml') && !abs.endsWith('.yml')) {
      throw new Error(`Not a YAML file: ${abs}`);
    }
    return [abs];
  }

  if (stat.isDirectory()) {
    const direct = path.join(abs, 'requirements.yaml');
    if (fs.existsSync(direct)) {
      return [direct];
    }

    const found = [];
    const walk = (dir) => {
      for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
          walk(full);
        } else if (entry.name === 'requirements.yaml' || entry.name === 'requirements.yml') {
          found.push(full);
        }
      }
    };
    walk(abs);
    return found.sort();
  }

  throw new Error(`Unsupported path: ${abs}`);
}

function formatAjvErrors(errors) {
  if (!errors || errors.length === 0) {
    return ['Unknown validation error'];
  }
  return errors.map((err) => {
    const instance = err.instancePath || '/';
    return `${instance} ${err.message}${err.params ? ` ${JSON.stringify(err.params)}` : ''}`;
  });
}

function normalizeComparable(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();
}

function companionMarkdownPath(filePath, specId) {
  const dir = path.dirname(filePath);
  const candidates = [];
  if (typeof specId === 'string' && specId.length > 0) {
    candidates.push(path.join(dir, `${specId}.md`));
  }
  candidates.push(path.join(dir, 'README.md'));
  return candidates.find((candidate) => fs.existsSync(candidate)) || null;
}

function validateMarkdownPair(filePath, data) {
  const specId = data && typeof data.specId === 'string' ? data.specId : null;
  if (companionMarkdownPath(filePath, specId)) {
    return [];
  }
  const expected = specId ? `${specId}.md or README.md` : '<specId>.md or README.md';
  return [`missing companion markdown (${expected}) in ${path.dirname(filePath)}`];
}

function isStubCriterion(req, criterion) {
  const given = String(criterion.given || '');
  const when = String(criterion.when || '');
  const then = normalizeComparable(criterion.then);
  const title = normalizeComparable(req.title);
  const placeholderPair = PLACEHOLDER_GIVEN.test(given) && PLACEHOLDER_WHEN.test(when);
  const thenRepeatsTitle = then.length > 0 && then === title;
  return placeholderPair || thenRepeatsTitle;
}

/** @deprecated Use isStubCriterion */
function isTautologicalCriterion(req, criterion) {
  return isStubCriterion(req, criterion);
}

function collectAcceptanceQuality(data) {
  const errors = [];
  const warnings = [];
  if (!data || !Array.isArray(data.requirements)) {
    return { errors, warnings };
  }

  data.requirements.forEach((req, reqIndex) => {
    if (!req || !Array.isArray(req.acceptanceCriteria)) {
      return;
    }
    req.acceptanceCriteria.forEach((criterion, acIndex) => {
      if (!criterion || typeof criterion !== 'object') {
        return;
      }
      ['given', 'when', 'then'].forEach((field) => {
        const value = typeof criterion[field] === 'string' ? criterion[field].trim() : '';
        const instance = `/requirements/${reqIndex}/acceptanceCriteria/${acIndex}/${field}`;
        if (value.length === 0) {
          errors.push(`${instance} must not be empty`);
        } else if (value.length < AC_FIELD_MIN_LENGTH) {
          warnings.push(
            `${instance} shorter than ${AC_FIELD_MIN_LENGTH} characters`
          );
        }
      });
      if (isStubCriterion(req, criterion)) {
        const id = typeof req.id === 'string' ? req.id : `#${reqIndex}`;
        warnings.push(
          `/requirements/${reqIndex} ${id} stub acceptance criterion (placeholder given/when or then repeats title)`
        );
      }
    });
  });

  return { errors, warnings };
}

function validateCatalogDocument(filePath, data, validateCatalog) {
  const errors = [];

  if (!validateCatalog(data)) {
    for (const msg of formatAjvErrors(validateCatalog.errors)) {
      errors.push(msg);
    }
  }

  if (data && Array.isArray(data.requirements)) {
    const seen = new Set();
    data.requirements.forEach((req, index) => {
      if (!req || typeof req.id !== 'string') {
        return;
      }
      if (seen.has(req.id)) {
        errors.push(`/requirements/${index}/id duplicate id "${req.id}" within catalog`);
      }
      seen.add(req.id);
    });
  }

  return errors;
}

function indexSpecIds(yamlFiles) {
  const bySpecId = new Map();
  for (const file of yamlFiles) {
    try {
      const data = yaml.load(fs.readFileSync(file, 'utf8'));
      if (data && typeof data.specId === 'string') {
        bySpecId.set(data.specId, file);
      }
    } catch {
      // parse errors reported during validation
    }
  }
  return bySpecId;
}

function validateDependsOn(data, filePath, specIndex) {
  const errors = [];
  if (!data || !Array.isArray(data.dependsOn)) {
    return errors;
  }
  for (const dep of data.dependsOn) {
    if (!specIndex.has(dep)) {
      errors.push(
        `dependsOn "${dep}" not found among catalogs in this validation run (referenced from ${path.relative(REPO_ROOT, filePath)})`
      );
    }
  }
  return errors;
}

function validateFiles(files, options = {}) {
  const { validateCatalog } = options.validateCatalog
    ? { validateCatalog: options.validateCatalog }
    : createAjv();
  if (!validateCatalog) {
    throw new Error('Failed to compile requirements-catalog.schema.json');
  }

  const uniqueFiles = [...new Set(files)];
  const specIndex = indexSpecIds(uniqueFiles);
  const results = [];

  for (const file of uniqueFiles) {
    let data;
    try {
      data = yaml.load(fs.readFileSync(file, 'utf8'));
    } catch (err) {
      results.push({
        file,
        ok: false,
        errors: [`YAML parse error: ${err.message}`],
        warnings: [],
        data: null,
      });
      continue;
    }

    const quality = collectAcceptanceQuality(data);
    const errors = [
      ...validateCatalogDocument(file, data, validateCatalog),
      ...validateMarkdownPair(file, data),
      ...validateDependsOn(data, file, specIndex),
      ...quality.errors,
    ];

    results.push({
      file,
      ok: errors.length === 0,
      errors,
      warnings: quality.warnings,
      data,
    });
  }

  return results;
}

function main(argv = process.argv.slice(2)) {
  const targets = argv.length > 0 ? argv : defaultTargets();

  if (targets.length === 0) {
    log('No ai-docs/*/requirements trees found.', 'red');
    log('Pass an explicit path to a requirements.yaml, feature dir, or tree.', 'yellow');
    return 1;
  }

  let files = [];
  try {
    for (const target of targets) {
      files = files.concat(collectYamlFiles(target));
    }
  } catch (err) {
    log(err.message, 'red');
    return 1;
  }

  files = [...new Set(files)];

  if (files.length === 0) {
    log('No requirements.yaml files found.', 'yellow');
    return 1;
  }

  log(`Validating ${files.length} catalog(s)...`, 'cyan');
  const results = validateFiles(files);
  let failed = 0;
  let warningCount = 0;

  for (const result of results) {
    const rel = path.relative(REPO_ROOT, result.file) || result.file;
    if (!result.ok) {
      failed += 1;
      log(`FAIL ${rel}`, 'red');
      for (const err of result.errors) {
        log(`  ${err}`, 'red');
      }
    } else {
      const count = result.data && Array.isArray(result.data.requirements)
        ? result.data.requirements.length
        : 0;
      const specId = result.data && result.data.specId ? result.data.specId : 'unknown';
      log(`OK   ${rel} (${specId}, ${count} requirements)`, 'green');
    }
    for (const warning of result.warnings) {
      warningCount += 1;
      log(`  WARN ${warning}`, 'yellow');
    }
  }

  console.log('');
  if (failed > 0) {
    log(`${failed} of ${results.length} catalog(s) failed.`, 'red');
    return 1;
  }

  log(`All ${results.length} catalog(s) passed.`, 'green');
  if (warningCount > 0) {
    log(`${warningCount} acceptance-criterion warning(s).`, 'yellow');
  }
  return 0;
}

module.exports = {
  AC_FIELD_MIN_LENGTH,
  DEFAULT_TARGET,
  REPO_ROOT,
  collectAcceptanceQuality,
  collectYamlFiles,
  companionMarkdownPath,
  createAjv,
  isStubCriterion,
  isTautologicalCriterion,
  main,
  validateCatalogDocument,
  validateDependsOn,
  validateFiles,
  validateMarkdownPair,
};

if (require.main === module) {
  process.exit(main());
}
