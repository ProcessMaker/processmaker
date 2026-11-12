const PREVIEW_OPEN_DEBOUNCE_MS = 400;

let lastPreviewOpen = { processId: null, at: 0 };

/**
 * Emit subprocess-preview-open, debouncing repeated opens of the same process.
 *
 * @param {{ processId: number|null, nodeId?: string }} payload
 * @returns {boolean} whether the event was emitted
 */
export function emitSubProcessPreviewOpen(payload) {
  const processId = payload?.processId || null;

  if (processId) {
    const now = Date.now();
    if (
      lastPreviewOpen.processId === processId
      && now - lastPreviewOpen.at < PREVIEW_OPEN_DEBOUNCE_MS
    ) {
      return false;
    }
    lastPreviewOpen = { processId, at: now };
  }

  window.ProcessMaker.EventBus.$emit("subprocess-preview-open", payload);
  return true;
}

/**
 * Resolve child process id from a Call Activity (Sub Process) BPMN node.
 *
 * @param {object|null} definition modeler node definition (moddle element or plain)
 * @returns {number|null}
 */
export function resolveSubProcessId(definition) {
  if (!definition) {
    return null;
  }

  let rawConfig = null;
  if (typeof definition.get === "function") {
    rawConfig = definition.get("config");
  } else {
    rawConfig = definition.config;
  }

  try {
    if (rawConfig) {
      const config = typeof rawConfig === "string" ? JSON.parse(rawConfig) : rawConfig;
      const id = config?.processId;
      if (id !== undefined && id !== null && id !== "") {
        const numeric = Number(id);
        if (Number.isFinite(numeric) && numeric > 0) {
          return numeric;
        }
      }
    }
  } catch {
    // fall through to calledElement
  }

  let calledElement = null;
  if (typeof definition.get === "function") {
    calledElement = definition.get("calledElement");
  } else {
    calledElement = definition.calledElement;
  }

  if (typeof calledElement === "string") {
    const match = calledElement.match(/-(\d+)$/);
    if (match) {
      const numeric = Number(match[1]);
      if (Number.isFinite(numeric) && numeric > 0) {
        return numeric;
      }
    }
  }

  return null;
}

/**
 * @param {number} processId
 * @returns {Promise<string|null>}
 */
export async function fetchSubProcessPreviewName(processId) {
  const { data } = await ProcessMaker.apiClient.get(`/processes/${processId}`);

  return data.name || null;
}

/**
 * @param {object} node modeler graph node
 * @returns {{ processId: number|null, nodeId?: string }}
 */
export function subProcessPreviewPayloadFromNode(node) {
  const definition = node?.definition;

  return {
    processId: resolveSubProcessId(definition),
    nodeId: definition?.id,
  };
}
