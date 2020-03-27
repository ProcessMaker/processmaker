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
const hasSignalEventDefinition = hasSubType('bpmn:signalEventDefinition');

const prependBpmnNamespace = (nodeTagName) => {
  if (nodeTagName.includes('bpmn:'))
  {
    return nodeTagName;
  }
  return `bpmn:${nodeTagName}`;
};

const getFullyQualifiedNodeType = (node) => {
  const tagName = prependBpmnNamespace(node.tagName);

  if (hasTimerEventDefinition(node)) {
    return `${tagName}:timerEventDefinition`;
  }
  if (hasMessageEventDefinition(node)) {
    return `${tagName}:messageEventDefinition`;
  }
  if (hasErrorEventDefinition(node)) {
    return `${tagName}:errorEventDefinition`;
  }
  if (hasSignalEventDefinition(node)) {
    return `${tagName}:signalEventDefinition`;
  }
  return tagName;
};

export default getFullyQualifiedNodeType;
