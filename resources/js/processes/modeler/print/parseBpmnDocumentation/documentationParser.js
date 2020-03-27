/**
 * Extract the .textContent of the first child of bpmnNode
 * that has type childType. Returns '' if no such child exists.
 */
const extractTextContentOfFirstChildWithType = (bpmnNode, childType) => {
  for (let entry of bpmnNode.childNodes.entries()) {
    if (!entry.length > 1 || entry[1].tagName !== childType) {
      continue;
    }
    return entry[1].textContent;
  }
  return '';
};

const isTextAnnotationElement = (node) => {
  return node.tagName === 'bpmn:textAnnotation';
};

/**
 * Return the documentation of a node, if it exists.
 */
const nodeDocumentation = (bpmnNode) => {
  return extractTextContentOfFirstChildWithType(bpmnNode, 'bpmn:documentation');
};

/**
 * Return the text child's contents of an annotation element,
 * if it exists.
 */
const nodeText = (bpmnNode) => {
  if (isTextAnnotationElement(bpmnNode)) {
    return extractTextContentOfFirstChildWithType(bpmnNode, 'bpmn:text');
  }
  return '';
};

export {nodeDocumentation, nodeText};
