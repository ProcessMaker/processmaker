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
import ModelerScreenSelect from './components/inspector/ScreenSelect';
import ExpressionEditor from './components/inspector/ExpressionEditor';

Vue.component('ModelerScreenSelect', ModelerScreenSelect);
Vue.component('ExpressionEditor', ExpressionEditor);

// Append to the inspector config of task
task.inspectorConfig[0].items.push({
  component: 'ModelerScreenSelect',
  config: {
    label: 'Screen For Input',
    helper: 'What Screen Should Be Used For Rendering This Task',
    name: 'screenRef'
  }
});

// Watcher:
// Add custom properties of sequenceFlow
sequenceFlow.inspectorHandler = (value, definition, component) => {
    // Go through each property and rebind it to our data
    Object.keys(value).forEach((key) => {
        console.log(key, value[key]);
        if (definition[key] !== value[key]) {
            definition[key] = value[key];
        }
        if (key === "conditionExpression") {
            definition[key].$type = "bpmn:Expression";
        }
    });
    component.updateShape();
};
// Add inspector for conditionExpression
sequenceFlow.inspectorConfig[0].items.push({
    component: "ExpressionEditor",
    config: {
        label: "Condition",
        helper: "An optional boolean Expression that acts as a gating condition",
        name: "id",
        property: "conditionExpression.body"
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

