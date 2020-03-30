/**
 * Extract the .textContent of the children of bpmnNode
 * that has tagName == childType. Returns '' if no such child exists.
 */
const extractTextContentOfChildren = (bpmnNode, childType) => {
  let textContent = '';
  bpmnNode.childNodes.forEach(function(childNode) {
    if (childNode.tagName === childType) {
      textContent += childNode.textContent;
    }
  });
  return textContent;
};

const isTextAnnotationElement = (node) => {
  return node.tagName === 'bpmn:textAnnotation';
};

/**
 * Return the documentation of a node, if it exists.
 */
const nodeDocumentation = (bpmnNode) => {
  return extractTextContentOfChildren(bpmnNode, 'bpmn:documentation');
};

/**
 * Return the text child's contents of an annotation element,
 * if it exists.
 */
const nodeText = (bpmnNode) => {
  if (isTextAnnotationElement(bpmnNode)) {
    return extractTextContentOfChildren(bpmnNode, 'bpmn:text');
  }
  return '';
};

export {nodeDocumentation, nodeText};
