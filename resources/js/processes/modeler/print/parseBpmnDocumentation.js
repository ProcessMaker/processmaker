function documentableBpmnNodes(bpmnString) {
  const bpmnDoc = new DOMParser().parseFromString(bpmnString, 'text/xml');

  const nodesWithNonEmptyId = Array.from(bpmnDoc.querySelectorAll('*[id]:not([id=""])'));

  const withNonEmptyNames = nodesWithNonEmptyId.filter((bpmnNode) => {
    const nodesToIncludeAnyway = [
      'bpmn:textAnnotation',
      'bpmn:messageFlow',
      'bpmn:sequenceFlow',
    ];
    return hasNonEmptyName(bpmnNode) || nodesToIncludeAnyway.includes(bpmnNode.tagName);
  });

  const withoutProcessNode = withNonEmptyNames.filter((bpmnNode) => {
    return !['bpmn:process', 'bpmn:error', 'bpmn:message'].includes(bpmnNode.tagName);
  });

  return withoutProcessNode.map((bpmnNode) => {
    const nodeType = addSubType(bpmnNode);
    return {
      id: bpmnNode.attributes.getNamedItem('id').textContent,
      type: nodeType,
      name: name(bpmnNode),
      documentationHtml: nodeDocumentation(bpmnNode),
      textHtml: nodeText(bpmnNode),
    };
  });
}

const hasSubType = (subType) => (parentNode) => hasChildNode(parentNode, subType);
const hasTimerEventDefinition = hasSubType('bpmn:timerEventDefinition');
const hasMessageEventDefinition = hasSubType('bpmn:messageEventDefinition');
const hasErrorEventDefinition = hasSubType('bpmn:errorEventDefinition');

const name = (bpmnNode) => {
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
  return name(node) !== '';
};


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

const nodeDocumentation = (bpmnNode) => {
  return extractTextContentOfFirstChildWithType(bpmnNode, 'bpmn:documentation');
};

const isTextAnnotationElement = (node) => {
  return node.tagName === 'bpmn:textAnnotation';
};

const nodeText = (bpmnNode) => {
  if (isTextAnnotationElement(bpmnNode)) {
    return extractTextContentOfFirstChildWithType(bpmnNode, 'bpmn:text');
  }
  return '';
};

const addSubType = (node) => {
  if (hasTimerEventDefinition(node)) {
    return `${node.tagName}:timerEventDefinition`;
  }
  if (hasMessageEventDefinition(node)) {
    return `${node.tagName}:messageEventDefinition`;
  }
  if (hasErrorEventDefinition(node)) {
    return `${node.tagName}:errorEventDefinition`;
  }
  return node.tagName;
};

const hasChildNode = (node, childType) => {
  for (let entry of node.childNodes.entries()) {
    if (!entry.length > 1 || entry[1].tagName === childType) {
      return true;
    }
  }
  return false;
};

export default documentableBpmnNodes;
