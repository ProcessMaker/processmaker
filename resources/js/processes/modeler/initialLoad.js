// Our initial node types to register with our modeler
import {
    association,
    endEvent,
    exclusiveGateway,
    // inclusiveGateway,
    parallelGateway,
    sequenceFlow,
    startEvent,
    task,
    scriptTask,
    pool,
    poolLane,
    textAnnotation,
    messageFlow,
    serviceTask,
    startTimerEvent,
    intermediateTimerEvent
} from '@processmaker/modeler';
import bpmnExtension from '@processmaker/processmaker-bpmn-moddle/resources/processmaker.json';
import ModelerScreenSelect from './components/inspector/ScreenSelect';
import ExpressionEditor from './components/inspector/ExpressionEditor';
import TaskAssignment from './components/inspector/TaskAssignment';
import ConfigEditor from './components/inspector/ConfigEditor';
import ScriptSelect from './components/inspector/ScriptSelect';
import Webhook from './components/inspector/Webhook';

Vue.component('ModelerScreenSelect', ModelerScreenSelect);
Vue.component('ExpressionEditor', ExpressionEditor);
Vue.component('TaskAssignment', TaskAssignment);
Vue.component('ConfigEditor', ConfigEditor);
Vue.component('ScriptSelect', ScriptSelect);
Vue.component('Webhook', Webhook);

let nodeTypes = [
    startEvent,
    endEvent,
    task,
    scriptTask,
    exclusiveGateway,
    //inclusiveGateway,
    parallelGateway,
    sequenceFlow,
    textAnnotation,
    association,
    pool,
    poolLane,
    messageFlow,
    serviceTask
]
ProcessMaker.nodeTypes.push(...nodeTypes);

// Set default properties for task
task.definition = function definition(moddle) {
    return moddle.create('bpmn:Task', {
        name: 'New Task',
        assignment: 'requestor',
    });
};

ProcessMaker.EventBus.$on('modeler-init', ({registerNode, registerBpmnExtension, registerInspectorExtension}) => {
    /* Register basic node types */
    for (const node of nodeTypes) {
        registerNode(node);
    }

    registerNode(startTimerEvent, definition => {
        const eventDefinitions = definition.get('eventDefinitions');
        if (definition.$type === 'bpmn:StartEvent' && eventDefinitions && eventDefinitions.length && eventDefinitions[0].$type === 'bpmn:TimerEventDefinition') {
          return 'processmaker-modeler-start-timer-event';
        }
      });
      registerNode(intermediateTimerEvent, definition => {
        const eventDefinitions = definition.get('eventDefinitions');
        if (definition.$type === 'bpmn:IntermediateCatchEvent' && eventDefinitions && eventDefinitions.length && eventDefinitions[0].$type === 'bpmn:TimerEventDefinition') {
          return 'processmaker-modeler-intermediate-catch-timer-event';
        }
      });

    /* Add a BPMN extension */
    registerBpmnExtension('pm', bpmnExtension);
    
    /* Register extension for webhooks */
    registerInspectorExtension(startEvent, {
        component: 'Webhook',
        config: {
            label: 'Webhook',
            helper: '',
            name: ''
        }
    });

    /* Register the inspector extensions for tasks */
    registerInspectorExtension(task, {
        component: 'ModelerScreenSelect',
        config: {
            label: 'Screen For Input',
            helper: 'What Screen Should Be Used For Rendering This Task',
            name: 'screenRef',
            type: 'FORM'
        }
    });
    registerInspectorExtension(task, {
        component: "TaskAssignment",
        config: {
            label: "Task Assignment",
            helper: "",
            name: "id"
        }
    });

    /* Register the inspector extensions for script tasks */
    registerInspectorExtension(scriptTask, {
        component: 'ScriptSelect',
        config: {
            label: 'Script',
            helper: 'Script that will be executed by the task',
            name: 'scriptRef'
        }
    });
    registerInspectorExtension(scriptTask, {
        component: 'ConfigEditor',
        config: {
            label: 'Script Configuration',
            helper: 'Configuration JSON for the script task',
            name: 'id',
            property: 'config',
        }
    });
    registerInspectorExtension(endEvent, {
        component: 'ModelerScreenSelect',
        config: {
            label: 'Summary screen',
            helper: 'Summary screen that will be displayed when process finish with this End event.',
            name: 'screenRef',
            params: { type: 'DISPLAY' }
        }
    });
});
