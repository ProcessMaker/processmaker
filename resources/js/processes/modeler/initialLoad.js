// Our initial node types to register with our modeler
import {
  association,
  endEvent,
  exclusiveGateway,
  inclusiveGateway,
  parallelGateway,
  sequenceFlow,
  startEvent,
  task,
  textAnnotation
} from '@processmaker/modeler'

import bpmnExtension from '@processmaker/processmaker-bpmn-moddle/resources/processmaker.json';

import BpmnModdle from 'bpmn-moddle';

let moddle = new BpmnModdle({
    pm: bpmnExtension
});

// Bring in our screen selector
import ModelerScreenSelect from './components/inspector/ScreenSelect'

Vue.component('ModelerScreenSelect', ModelerScreenSelect);

// Append to the inspector config of task
task.inspectorConfig[0].items.push({
  component: 'ModelerScreenSelect',
  config: {
    label: 'Screen For Input',
    helper: 'What Screen Should Be Used For Rendering This Task',
    name: 'screenRef'
  }
});

let nodeTypes = [
  startEvent,
  endEvent,
  task,
  exclusiveGateway,
  //inclusiveGateway,
  //parallelGateway,
  sequenceFlow,
  textAnnotation,
  association,
]

ProcessMaker.EventBus.$on('modeler-init', function (modeler) {
  modeler.registerBpmnExtension('pm', bpmnExtension);
  for (var node of nodeTypes) {
    modeler.registerNodeType(node);
  }
});

