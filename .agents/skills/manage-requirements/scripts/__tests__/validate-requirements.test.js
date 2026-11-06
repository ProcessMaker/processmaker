const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { test, beforeEach, afterEach } = require('node:test');

const {
  collectAcceptanceQuality,
  collectYamlFiles,
  isStubCriterion,
  main,
  validateFiles,
  validateMarkdownPair,
} = require('../validate-requirements');

const VALID_YAML = `specId: example-feature
kind: component
title: Example feature
description: Short summary of what this catalog covers.
config:
  props:
    - readonly
dependsOn: []
requirements:
  - id: FR-001
    requirementType: Functional
    title: Example behavior
    statement: The component must expose the configured behavior without hard-coding a specific screen.
    priority: High
    status: Draft
    tags: [example]
    acceptanceCriteria:
      - given: The component is configured with readonly true
        when: The user opens the feature
        then: Editing controls are disabled
`;

function writeCatalog(dir, { specId = 'example-feature', yaml = VALID_YAML, markdown = true } = {}) {
  const featureDir = path.join(dir, specId);
  fs.mkdirSync(featureDir, { recursive: true });
  fs.writeFileSync(path.join(featureDir, 'requirements.yaml'), yaml, 'utf8');
  if (markdown) {
    fs.writeFileSync(path.join(featureDir, `${specId}.md`), `# ${specId}\n\nPurpose.\n`, 'utf8');
  }
  return path.join(featureDir, 'requirements.yaml');
}

let tmpDir;

beforeEach(() => {
  tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'req-validate-'));
});

afterEach(() => {
  fs.rmSync(tmpDir, { recursive: true, force: true });
});

test('collectYamlFiles finds nested requirements.yaml', () => {
  writeCatalog(tmpDir);
  const files = collectYamlFiles(tmpDir);
  assert.equal(files.length, 1);
  assert.match(files[0], /example-feature\/requirements\.yaml$/);
});

test('valid catalog with companion markdown passes', () => {
  const yamlPath = writeCatalog(tmpDir);
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, true);
  assert.deepEqual(result.errors, []);
  assert.deepEqual(result.warnings, []);
});

test('YAML parse error fails the catalog', () => {
  const yamlPath = writeCatalog(tmpDir, {
    yaml: 'specId: [this is: broken',
  });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, false);
  assert.match(result.errors[0], /YAML parse error/);
});

test('duplicate requirement ids fail', () => {
  const yaml = VALID_YAML.replace(
    'then: Editing controls are disabled\n',
    `then: Editing controls are disabled
  - id: FR-001
    requirementType: Functional
    title: Duplicate behavior
    statement: Another statement that is long enough for the schema.
    priority: High
    status: Draft
    acceptanceCriteria:
      - given: The component is configured with readonly false
        when: The user opens the feature
        then: Editing controls remain enabled
`
  );
  const yamlPath = writeCatalog(tmpDir, { yaml });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, false);
  assert.ok(result.errors.some((err) => /duplicate id "FR-001"/.test(err)));
});

test('functional requirement without acceptanceCriteria fails schema', () => {
  const yaml = `specId: example-feature
kind: component
title: Example feature
requirements:
  - id: FR-001
    requirementType: Functional
    title: Example behavior
    statement: The component must expose the configured behavior without hard-coding a specific screen.
    priority: High
    status: Draft
`;
  const yamlPath = writeCatalog(tmpDir, { yaml });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, false);
  assert.ok(result.errors.some((err) => /required/.test(err)));
});

test('missing companion markdown fails', () => {
  const yamlPath = writeCatalog(tmpDir, { markdown: false });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, false);
  assert.ok(result.errors.some((err) => /missing companion markdown/.test(err)));
  assert.ok(validateMarkdownPair(yamlPath, { specId: 'example-feature' }).length > 0);
});

test('README.md satisfies the markdown pair', () => {
  const yamlPath = writeCatalog(tmpDir, { markdown: false });
  fs.writeFileSync(path.join(path.dirname(yamlPath), 'README.md'), '# Example\n', 'utf8');
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, true);
});

test('short given/when/then warn instead of failing', () => {
  const yaml = VALID_YAML.replace(
    'given: The component is configured with readonly true',
    'given: short'
  );
  const yamlPath = writeCatalog(tmpDir, { yaml });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, true);
  assert.ok(result.warnings.some((warn) => /given shorter than 8/.test(warn)));
});

test('empty given/when/then fail', () => {
  const yaml = VALID_YAML.replace(
    'given: The component is configured with readonly true',
    'given: "   "'
  );
  const yamlPath = writeCatalog(tmpDir, { yaml });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, false);
  assert.ok(result.errors.some((err) => /given must not be empty/.test(err)));
});

test('placeholder given/when is a stub AC warning, not a hard fail', () => {
  const yaml = VALID_YAML.replace(
    `      - given: The component is configured with readonly true
        when: The user opens the feature
        then: Editing controls are disabled
`,
    `      - given: The feature is available in an active CollegeV2 case
        when: The behavior is exercised
        then: Editing controls are disabled
`
  );
  const yamlPath = writeCatalog(tmpDir, { yaml });
  const [result] = validateFiles([yamlPath]);
  assert.equal(result.ok, true);
  assert.ok(result.warnings.some((warn) => /stub acceptance criterion/.test(warn)));
});

test('then that repeats the title is a stub AC', () => {
  assert.equal(
    isStubCriterion(
      { title: 'Hide student search in Web Entry' },
      { given: 'A screen is open today', when: 'The reviewer continues', then: 'Hide student search in Web Entry' }
    ),
    true
  );
  const quality = collectAcceptanceQuality({
    requirements: [
      {
        id: 'FR-001',
        title: 'Hide student search in Web Entry',
        acceptanceCriteria: [
          {
            given: 'A screen is open today',
            when: 'The reviewer continues',
            then: 'Hide student search in Web Entry',
          },
        ],
      },
    ],
  });
  assert.equal(quality.errors.length, 0);
  assert.equal(quality.warnings.length, 1);
});

test('CLI main returns 0 for a valid tree and 1 when markdown is missing', () => {
  writeCatalog(tmpDir);
  assert.equal(main([tmpDir]), 0);
  const brokenDir = path.join(tmpDir, 'broken');
  writeCatalog(brokenDir, { specId: 'missing-md', markdown: false });
  assert.equal(main([brokenDir]), 1);
});
