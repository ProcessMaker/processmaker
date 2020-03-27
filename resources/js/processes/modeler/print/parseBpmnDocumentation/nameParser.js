const getNodeName = (bpmnNode) => {
  const name = bpmnNode.attributes.getNamedItem('name');
  if (!name || !name.textContent) {
    switch (bpmnNode.tagName) {
      case 'bpmn:sequenceFlow':
        return 'Unnamed Sequence Flow';
      case 'bpmn:messageFlow':
        return 'Unnamed Message Flow';
      case 'bpmn:textAnnotation':
        return 'Annotation';
      default:
        return '';
    }
  }
  return name.textContent;
};

const hasNonEmptyName = (node) => {
  return getNodeName(node) !== '';
};

export {getNodeName, hasNonEmptyName};
