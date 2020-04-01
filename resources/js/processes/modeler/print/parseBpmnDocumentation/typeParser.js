const hasChildNode = (node, childType) => {
  let found = false;
  node.childNodes.forEach(
      function(childNode) {
        if (childNode.tagName && childNode.tagName.includes(childType)) {
          found = true;
        }
      });
  return found;
};

const hasSubType = (subType) => (parentNode) => hasChildNode(parentNode, subType);
const hasTimerEventDefinition = hasSubType('timerEventDefinition');
const hasMessageEventDefinition = hasSubType('messageEventDefinition');
const hasErrorEventDefinition = hasSubType('errorEventDefinition');
const hasSignalEventDefinition = hasSubType('signalEventDefinition');

const prependBpmnNamespace = (nodeTagName) => {
  if (nodeTagName.startsWith('bpmn')) {
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
