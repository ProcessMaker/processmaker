import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { layoutBpmn } from '../layout.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..', '..', '..');

function loadFixture(name) {
  return readFileSync(join(__dirname, '..', 'fixtures', name), 'utf8');
}

test('layout spreads overlapping nodes left-to-right', async () => {
  const input = loadFixture('linear.bpmn');
  const { xml, warnings } = await layoutBpmn(input);
  assert.equal(warnings.length, 0);
  assert.match(xml, /pm:screenRef="1"/);
  const bounds = [...xml.matchAll(/dc:Bounds x="(\d+)"/g)].map((m) => Number(m[1]));
  assert.ok(bounds.length >= 3);
  assert.ok(bounds[1] > bounds[0], 'task should be to the right of start');
  assert.ok(bounds[2] > bounds[1], 'end should be to the right of task');
});

test('every sequence flow has at least two waypoints', async () => {
  const input = loadFixture('linear.bpmn');
  const { xml } = await layoutBpmn(input);
  const edges = (xml.match(/<bpmndi:BPMNEdge /g) ?? []).length;
  assert.equal(edges, 2);
  const waypointBlocks = [...xml.matchAll(/bpmndi:BPMNEdge[\s\S]*?<\/bpmndi:BPMNEdge>/g)];
  for (const block of waypointBlocks) {
    const count = (block[0].match(/di:waypoint/g) ?? []).length;
    assert.ok(count >= 2, `expected >= 2 waypoints, got ${count}`);
  }
});

test('laneSet emits lane shapes', async () => {
  const input = readFileSync(
    join(repoRoot, 'database/processes/inactive/VacationRequet.bpmn'),
    'utf8',
  );
  const { xml } = await layoutBpmn(input);
  assert.match(xml, /laneSet|lane/i);
  const laneShapes = (xml.match(/BPMNShape[^>]*bpmnElement="(_6|_7|_8)"/g) ?? []).length;
  assert.ok(laneShapes >= 3, `expected lane BPMNShapes, got ${laneShapes}`);
});
