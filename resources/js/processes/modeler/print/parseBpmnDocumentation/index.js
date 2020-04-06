import getFullyQualifiedNodeType from './typeParser';
import {getNodeName, hasNonEmptyName} from './nameParser';
import {nodeDocumentation, nodeText} from './documentationParser';

const allowedName = (bpmnNode) => {
  const nodesToIncludeAnyway = [
    'bpmn:textAnnotation',
    'bpmn:messageFlow',
    'bpmn:sequenceFlow',
  ];
  return hasNonEmptyName(bpmnNode) || nodesToIncludeAnyway.includes(bpmnNode.tagName);
};

const editorSpecificNodes = (bpmnNode) => {
  const adonis = 'adonis:';

  const appSpecificNamespaces = [adonis];

  return !appSpecificNamespaces.some((appNamespace) => bpmnNode.tagName.includes(appNamespace));
};

const undocumentableNodes = (bpmnNode) => {
  const nodesThatCannotBeDocumented = [
    'bpmn:process',
    'bpmn:error',
    'bpmn:message',
    'bpmn:signal',
    'signal',
  ];
  return !nodesThatCannotBeDocumented.includes(bpmnNode.tagName);
};

const nodeSort = (a, b) => {
  if (a.id === b.id) {
    return a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'});
  }
  return a.id.localeCompare(b.id, undefined, {numeric: true, sensitivity: 'base'});
};

function documentableBpmnNodes(bpmnString) {
  const bpmnDoc = new DOMParser().parseFromString(bpmnString, 'text/xml');
  return Array.from(bpmnDoc.querySelectorAll('*[id]:not([id=""])'))
      .filter(allowedName)
      .filter(editorSpecificNodes)
      .filter(undocumentableNodes)
      .sort(nodeSort)
      .map((bpmnNode) => {
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
