const hasChildNode = (node, childType) => {
  for (let entry of node.childNodes.entries()) {
    if (!entry.length > 1 || entry[1].tagName === childType) {
      return true;
    }
  }
  return false;
};

const hasSubType = (subType) => (parentNode) => hasChildNode(parentNode, subType);
const hasTimerEventDefinition = hasSubType('bpmn:timerEventDefinition');
const hasMessageEventDefinition = hasSubType('bpmn:messageEventDefinition');
const hasErrorEventDefinition = hasSubType('bpmn:errorEventDefinition');

const getFullyQualifiedNodeType = (node) => {
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

export default getFullyQualifiedNodeType;
