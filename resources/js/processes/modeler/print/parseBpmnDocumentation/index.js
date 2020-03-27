import getFullyQualifiedNodeType from './typeParser';
import {getNodeName, hasNonEmptyName} from './nameParser';
import {nodeDocumentation, nodeText} from './documentationParser';

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

  const documentableNodes = withNonEmptyNames.filter((bpmnNode) => {
    const nodesThatCannotBeDocumented = ['bpmn:process', 'bpmn:error', 'bpmn:message'];
    return !nodesThatCannotBeDocumented.includes(bpmnNode.tagName);
  });

  return documentableNodes.map((bpmnNode) => {
    return {
      id: bpmnNode.attributes.getNamedItem('id').textContent,
      type: getFullyQualifiedNodeType(bpmnNode),
      name: getNodeName(bpmnNode),
      documentationHtml: nodeDocumentation(bpmnNode),
      textHtml: nodeText(bpmnNode),
    };
  });
}

export default documentableBpmnNodes;
