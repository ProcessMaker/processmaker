import { emitSubProcessPreviewOpen, subProcessPreviewPayloadFromNode } from "./subprocessPreview";

const CALL_ACTIVITY_TYPE = "processmaker-modeler-call-activity";
const DOUBLE_CLICK_MS = 450;

/**
 * POC: open PM4-owned subprocess preview modal on double-click of a Sub Process node.
 *
 * @param {import('vue').default} Vue
 */
export function registerSubProcessPreviewHooks(Vue) {
  const scheduleAttachHooks = () => {
    attachSubProcessPreviewHooks(Vue);
    window.setTimeout(() => attachSubProcessPreviewHooks(Vue), 500);
    window.setTimeout(() => attachSubProcessPreviewHooks(Vue), 1500);
  };

  window.ProcessMaker.EventBus.$on("modeler-app-init", scheduleAttachHooks);
  window.ProcessMaker.EventBus.$on("modeler-start", scheduleAttachHooks);
  window.ProcessMaker.EventBus.$on("modeler-xml-loaded", scheduleAttachHooks);
  window.ProcessMaker.EventBus.$on("modeler-change", () => {
    window.setTimeout(() => attachSubProcessPreviewHooks(Vue), 0);
  });
}

function attachSubProcessPreviewHooks(Vue) {
  const modelerApp = window.$modelerApp;
  if (!modelerApp) {
    return;
  }

  Vue.nextTick(() => {
    attachCanvasDoubleClick(modelerApp);
    hijackBuiltInSubProcessPreview(modelerApp);
  });
}

function getSubProcessComponents(modeler) {
  const graph = modeler?.graph || modeler?.$store?.state?.graph;
  if (!graph?.getCells) {
    return [];
  }

  const components = [];
  graph.getCells().forEach((cell) => {
    const component = cell.component;
    const node = component?.node;
    if (node?.type === CALL_ACTIVITY_TYPE && component) {
      components.push({ component, node });
    }
  });

  return components;
}

function openPmSubProcessPreview(node, component) {
  const payload = subProcessPreviewPayloadFromNode(node);

  if (payload.processId) {
    component?.$refs?.["subprocess-modal"]?.hide?.();
    component?.$refs?.["no-subprocess-modal"]?.hide?.();
  }

  emitSubProcessPreviewOpen(payload);
}

function createPmClickSubprocessHandler(component, node) {
  return (shapeView, event) => {
    const isPlusMarkerTheTarget = event?.target?.getAttribute?.("joint-selector") === "bottomCenter.0";
    const isItThisShape = shapeView.model === component.shape;

    if (!isPlusMarkerTheTarget || !isItThisShape) {
      return;
    }

    if (event?.stopPropagation) {
      event.stopPropagation();
    }

    openPmSubProcessPreview(node, component);
  };
}

/**
 * The modeler package registers `clickSubprocess` on paperManager in mounted().
 * Replacing the method alone does not remove that listener — unregister it first.
 */
function hijackBuiltInSubProcessPreview(modelerApp) {
  const modeler = modelerApp?.$refs?.modeler;
  const entries = getSubProcessComponents(modeler);

  entries.forEach(({ component, node }) => {
    if (component.__pmSubProcessPreviewHijacked) {
      return;
    }

    const paperManager = component.paperManager;
    const registeredHandler = component.__pmOriginalClickSubprocess || component.clickSubprocess;

    if (paperManager && typeof registeredHandler === "function") {
      paperManager.removeEventHandler("element:pointerclick", registeredHandler);
      paperManager.removeEventHandler("element:pointerclick", registeredHandler, component);
    }

    if (!component.__pmOriginalClickSubprocess && typeof component.clickSubprocess === "function") {
      component.__pmOriginalClickSubprocess = component.clickSubprocess;
    }

    const pmHandler = createPmClickSubprocessHandler(component, node);
    component.clickSubprocess = pmHandler;

    if (paperManager) {
      paperManager.addEventHandler("element:pointerclick", pmHandler, component);
    }

    component.__pmSubProcessPreviewHijacked = true;
  });
}

function attachCanvasDoubleClick(modelerApp) {
  const modeler = modelerApp?.$refs?.modeler;
  const paperManager = modeler?.paperManager;
  if (!paperManager || paperManager.__pmSubProcessPreviewHook) {
    return;
  }

  let lastClick = { nodeId: null, at: 0 };

  const handler = (shapeView, event) => {
    const isPlusMarkerTheTarget = event?.target?.getAttribute?.("joint-selector") === "bottomCenter.0";
    if (isPlusMarkerTheTarget) {
      return;
    }

    const component = shapeView?.model?.component;
    const node = component?.node;
    if (!node || node.type !== CALL_ACTIVITY_TYPE) {
      return;
    }

    const nodeId = node.definition?.id;
    const now = Date.now();
    const isDouble = lastClick.nodeId === nodeId && now - lastClick.at < DOUBLE_CLICK_MS;
    lastClick = { nodeId, at: now };

    if (!isDouble) {
      return;
    }

    openPmSubProcessPreview(node, component);
  };

  paperManager.addEventHandler("element:pointerclick", handler);
  paperManager.__pmSubProcessPreviewHook = true;
}
