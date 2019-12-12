/* eslint-disable func-names */
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
  manualTask,
  pool,
  poolLane,
  textAnnotation,
  messageFlow,
  serviceTask,
  callActivity,
  eventBasedGateway
} from '@processmaker/modeler';
import ModelerScreenSelect from './components/inspector/ScreenSelect';
import UserSelect from './components/inspector/UserSelect';
import GroupSelect from './components/inspector/GroupSelect';
import UserById from './components/inspector/UserById';
import TaskNotifications from './components/inspector/TaskNotifications';
import ExpressionEditor from './components/inspector/ExpressionEditor';
import TaskAssignment from './components/inspector/TaskAssignment';
import TaskDueIn from './components/inspector/TaskDueIn';
import ConfigEditor from './components/inspector/ConfigEditor';
import ScriptSelect from './components/inspector/ScriptSelect';
import StartPermission from './components/inspector/StartPermission';
import {registerNodes} from "@processmaker/modeler";
import Interstitial from "./components/inspector/Interstitial";

Vue.component('UserSelect', UserSelect);
Vue.component('UserById', UserById);
Vue.component('GroupSelect', GroupSelect);
Vue.component('ModelerScreenSelect', ModelerScreenSelect);
Vue.component('TaskNotifications', TaskNotifications);
Vue.component('ExpressionEditor', ExpressionEditor);
Vue.component('TaskAssignment', TaskAssignment);
Vue.component('TaskDueIn', TaskDueIn);
Vue.component('ConfigEditor', ConfigEditor);
Vue.component('ScriptSelect', ScriptSelect);
Vue.component('StartPermission', StartPermission);
Vue.component("Interstitial", Interstitial);

let nodeTypes = [
  endEvent,
  task,
  scriptTask,
  manualTask,
  callActivity,
  exclusiveGateway,
  inclusiveGateway,
  eventBasedGateway,
  parallelGateway,
  sequenceFlow,
  association,
  pool,
  poolLane,
  messageFlow,
  serviceTask,
  textAnnotation,
];

ProcessMaker.nodeTypes.push(startEvent);
ProcessMaker.nodeTypes.push(...nodeTypes);

// Set default properties for task
task.definition = function definition(moddle) {
  return moddle.create('bpmn:Task', {
    name: window.ProcessMaker.events.$t('New Task'),
    assignment: 'requester'
  });
};
ProcessMaker.EventBus.$on('modeler-init', registerNodes);

ProcessMaker.EventBus.$on(
  'modeler-init',
  ({registerInspectorExtension}) => {
    /* Register extension for start permission */
    registerInspectorExtension(startEvent, {
      component: 'FormAccordion',
      container: true,
      config: {
        initiallyOpen: false,
        label: 'Start Permissions',
        icon: 'user-shield',
        name: 'startPermissions',
      },
      items: [
        {
          component: 'StartPermission',
          config: {
            label: 'Permission To Start',
            helper: 'Select who may start a Request of this Process',
            userHelper: 'Select who may start a Request',
            groupHelper: 'Select the group from which any user may start a Request',
            name: 'startPermission'
          }
        },
      ],
    });

    /* Register the inspector extensions for tasks */
    registerInspectorExtension(task, {
      component: 'ModelerScreenSelect',
      config: {
        label: 'Screen for Input',
        helper: 'Select Screen to display this Task',
        name: 'screenRef',
        type: 'FORM'
      }
    });
    registerInspectorExtension(task, {
      component: 'TaskDueIn',
      config: {
        label: 'Due In',
        helper: 'Time when the task will be due',
        name: 'taskDueIn',
      }
    });
    registerInspectorExtension(task, {
      component: 'FormAccordion',
      container: true,
      config: {
        initiallyOpen: false,
        label: 'Assignment Rules',
        icon: 'users',
        name: 'assignmentRules',
      },
      items: [
        {
          component: 'TaskAssignment',
          config: {
            label: 'Task Assignment',
            helper: '',
            name: 'taskAssignment'
          }
        },
      ],
    });
    registerInspectorExtension(task, {
      component: 'FormAccordion',
      container: true,
      config: {
        initiallyOpen: false,
        label: 'Notifications',
        icon: 'bell',
        name: 'notifications',
      },
      items: [
        {
          component: 'TaskNotifications',
          config: {
            helper: 'Users that should be notified about task events'
          }
        },
      ],
    });

    registerInspectorExtension(task, {
      component: "Interstitial",
      config: {
        label: "Display Next Assigned Task to Task Assignee",
        helper: "Directs Task assignee to the next assigned Task",
        name: "interstitial"
      }
    });

    /* Register the inspector extensions for script tasks */
    registerInspectorExtension(scriptTask, {
      component: 'ScriptSelect',
      config: {
        label: 'Script',
        helper: 'Select the Script this element runs',
        name: 'scriptRef'
      }
    });

    registerInspectorExtension(scriptTask, {
      component: 'ConfigEditor',
      config: {
        label: 'Script Configuration',
        helper: 'Enter the JSON to configure the Script',
        name: 'scriptConfiguration',
        property: 'config'
      }
    });
    registerInspectorExtension(endEvent, {
      component: 'ModelerScreenSelect',
      config: {
        label: 'Summary Screen',
        helper:
          'Select Display-type Screen to show the summary of this Request when it completes',
        name: 'screenRef',
        params: { type: 'DISPLAY' }
      }
    });
    registerInspectorExtension(manualTask, {
      component: 'ModelerScreenSelect',
      config: {
        label: 'Screen for Input',
        helper:
          'Select Screen to display this Task',
        name: 'screenRef',
        params: { type: 'DISPLAY' }
      }
    });
    registerInspectorExtension(manualTask, {
      component: 'TaskDueIn',
      config: {
        label: 'Due In',
        helper: 'Enter the hours until this Task is overdue',
        name: 'taskDueIn',
      }
    });
    registerInspectorExtension(manualTask, {
      component: 'FormAccordion',
      container: true,
      config: {
        initiallyOpen: false,
        label: 'Assignment Rules',
        icon: 'users',
        name: 'assignmentRules',
      },
      items: [
        {
          component: 'TaskAssignment',
          config: {
            label: 'Task Assignment',
            helper: '',
            name: 'taskAssignment'
          }
        },
      ],
    });
    registerInspectorExtension(manualTask, {
      component: 'FormAccordion',
      container: true,
      config: {
        initiallyOpen: false,
        label: 'Notifications',
        icon: 'bell',
        name: 'notifications',
      },
      items: [
        {
          component: 'TaskNotifications',
          config: {
            helper: 'Users that should be notified about task events'
          }
        },
      ],
    });
    registerInspectorExtension(manualTask, {
      component: "Interstitial",
      config: {
        label: "Enable Interstitial",
        helper: "redirected to my next assigned task",
        name: "interstitial"
      }
    });
  }
);
