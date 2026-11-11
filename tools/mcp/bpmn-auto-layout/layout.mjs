import { layoutProcess } from 'bpmn-auto-layout-extended';

/**
 * @param {string} xml
 * @param {{ expandSubProcesses?: boolean }} [options]
 * @returns {Promise<{ xml: string, warnings: string[] }>}
 */
export async function layoutBpmn(xml, options = {}) {
  const trimmed = xml.trim();
  if (trimmed === '') {
    throw new Error('BPMN XML is empty.');
  }

  const layoutOptions = {};
  if (options.expandSubProcesses === true) {
    layoutOptions.expandSubProcesses = true;
  }

  const result = await layoutProcess(trimmed, layoutOptions);
  const output = typeof result === 'string' ? result : result?.xml;
  if (typeof output !== 'string' || output.trim() === '') {
    throw new Error('BPMN auto-layout produced empty output.');
  }

  const warnings = [];
  if (typeof result === 'object' && result !== null && Array.isArray(result.warnings)) {
    for (const item of result.warnings) {
      if (typeof item === 'string') {
        warnings.push(item);
      } else if (item?.message) {
        warnings.push(String(item.message));
      }
    }
  }

  const withDeclaration = output.startsWith('<?xml') ? output : `<?xml version="1.0" encoding="UTF-8"?>\n${output}`;

  return { xml: withDeclaration, warnings };
}
