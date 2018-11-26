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
import ModelerScreenSelect from './components/inspector/ScreenSelect';
import ExpressionEditor from './components/inspector/ExpressionEditor';
import TaskAssignment from './components/inspector/TaskAssignment';

Vue.component('ModelerScreenSelect', ModelerScreenSelect);
Vue.component('ExpressionEditor', ExpressionEditor);
Vue.component('TaskAssignment', TaskAssignment);

// Append to the inspector config of task
task.inspectorConfig[0].items.push({
    component: 'ModelerScreenSelect',
    config: {
        label: 'Screen For Input',
        helper: 'What Screen Should Be Used For Rendering This Task',
        name: 'screenRef'
    }
});
task.inspectorConfig[0].items.push({
    component: "FormInput",
    config: {
        type: "number",
        label: "Due In",
        placeholder: "72 hours",
        helper: "Time when the task will due (hours)",
        name: "dueIn"
    }
});
task.inspectorConfig[0].items.push({
    component: "TaskAssignment",
    config: {
        label: "Task Assignment",
        helper: "",
        name: "id"
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

ProcessMaker.EventBus.$on('modeler-init', ({ registerNode, registerBpmnExtension })  => {
  /* Register basic node types */
  for (const node of nodeTypes) {
    registerNode(node, () => node.id);
  }

  /* Add a BPMN extension */
  registerBpmnExtension('pm', bpmnExtension);
});


