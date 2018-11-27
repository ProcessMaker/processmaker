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
        scriptTask,
        textAnnotation
} from '@processmaker/modeler';
import bpmnExtension from '@processmaker/processmaker-bpmn-moddle/resources/processmaker.json';
import ModelerScreenSelect from './components/inspector/ScreenSelect';
import ExpressionEditor from './components/inspector/ExpressionEditor';
import TaskAssignment from './components/inspector/TaskAssignment';
import ConfigEditor from './components/inspector/ConfigEditor';
import ScriptSelect from './components/inspector/ScriptSelect';

Vue.component('ModelerScreenSelect', ModelerScreenSelect);
Vue.component('ExpressionEditor', ExpressionEditor);
Vue.component('TaskAssignment', TaskAssignment);
Vue.component('ConfigEditor', ConfigEditor);
Vue.component('ScriptSelect', ScriptSelect);

let nodeTypes = [
    startEvent,
    endEvent,
    task,
    scriptTask,
    exclusiveGateway,
    //inclusiveGateway,
    //parallelGateway,
    sequenceFlow,
    textAnnotation,
    association,
]

ProcessMaker.EventBus.$on('modeler-init', ({ registerNode, registerBpmnExtension, registerInspectorExtension }) => {
    /* Register basic node types */
    for (const node of nodeTypes) {
        registerNode(node, () => node.id);
    }

    /* Add a BPMN extension */
    registerBpmnExtension('pm', bpmnExtension);

    /* Register the inspector extensions for tasks */
    registerInspectorExtension(task, {
        component: 'ModelerScreenSelect',
        config: {
            label: 'Screen For Input',
            helper: 'What Screen Should Be Used For Rendering This Task',
            name: 'screenRef'
        }
    });
    registerInspectorExtension(task, {
        component: "FormInput",
        config: {
            type: "number",
            label: "Due In",
            placeholder: "72 hours",
            helper: "Time when the task will due (hours)",
            name: "dueIn"
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
});
