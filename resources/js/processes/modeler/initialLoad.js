/* eslint-disable func-names */
// Our initial node types to register with our modeler
import {
  association,
  endEvent,
  terminateEndEvent,
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
  eventBasedGateway,
  intermediateMessageCatchEvent,
  intermediateSignalThrowEvent,
  signalEndEvent,
  loopCharacteristicsInspector,
  loopCharacteristicsHandler,
  loopCharacteristicsData,
  NodeIdentifierInput,
} from "@processmaker/modeler";
import { registerNodes } from "@processmaker/modeler";
import i18next from "i18next";
import ModelerScreenSelect from "./components/inspector/ScreenSelect";
import UserSelect from "./components/inspector/UserSelect";
import GroupSelect from "./components/inspector/GroupSelect";
import UserById from "./components/inspector/UserById";
import TaskNotifications from "./components/inspector/TaskNotifications";
import ExpressionEditor from "./components/inspector/ExpressionEditor";
import TaskAssignment from "./components/inspector/TaskAssignment";
import TaskDueIn from "./components/inspector/TaskDueIn";
import GatewayFlowVariable from "./components/inspector/GatewayFlowVariable";
import ConfigEditor from "./components/inspector/ConfigEditor";
import SignalPayload from "./components/inspector/SignalPayload";
import ScriptSelect from "./components/inspector/ScriptSelect";
import StartPermission from "./components/inspector/StartPermission";
import Interstitial from "./components/inspector/Interstitial";
import SelectUserGroup from "../../components/SelectUserGroup";
import validateScreenRef from "./validateScreenRef";
import validateFlowGenieRef from "./validateFlowGenieRef";
import ErrorHandlingTimeout from "./components/inspector/ErrorHandlingTimeout";
import ErrorHandlingRetryAttempts from "./components/inspector/ErrorHandlingRetryAttempts";
import ErrorHandlingRetryWaitTime from "./components/inspector/ErrorHandlingRetryWaitTime";
import NotifyProcessManager from "./components/inspector/NotifyProcessManager";

Vue.component("UserSelect", UserSelect);
Vue.component("UserById", UserById);
Vue.component("GroupSelect", GroupSelect);
Vue.component("ModelerScreenSelect", ModelerScreenSelect);
Vue.component("TaskNotifications", TaskNotifications);
Vue.component("ExpressionEditor", ExpressionEditor);
Vue.component("TaskAssignment", TaskAssignment);
Vue.component("TaskDueIn", TaskDueIn);
Vue.component("GatewayFlowVariable", GatewayFlowVariable);
Vue.component("ConfigEditor", ConfigEditor);
Vue.component("SignalPayload", SignalPayload);
Vue.component("ScriptSelect", ScriptSelect);
Vue.component("StartPermission", StartPermission);
Vue.component("Interstitial", Interstitial);
Vue.component("SelectUserGroup", SelectUserGroup);
Vue.component("NodeIdentifierInput", NodeIdentifierInput);
Vue.component("ErrorHandlingTimeout", ErrorHandlingTimeout);
Vue.component("ErrorHandlingRetryAttempts", ErrorHandlingRetryAttempts);
Vue.component("ErrorHandlingRetryWaitTime", ErrorHandlingRetryWaitTime);
Vue.component("NotifyProcessManager", NotifyProcessManager);

const nodeTypes = [
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
  intermediateMessageCatchEvent,
  intermediateSignalThrowEvent,
  signalEndEvent,
  eventBasedGateway,
];

ProcessMaker.nodeTypes.push(startEvent);
ProcessMaker.nodeTypes.push(...nodeTypes);
ProcessMaker.modelerExtensions = {
  loopCharacteristicsInspector,
  loopCharacteristicsHandler,
  loopCharacteristicsData,
  NodeIdentifierInput,
};

ProcessMaker.EventBus.$on("modeler-init", registerNodes);

ProcessMaker.EventBus.$on(
  "modeler-init",
  ({ registerInspectorExtension }) => {
    /* Register extension for start permission */
    registerInspectorExtension(startEvent, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Start Permissions"),
        icon: "user-shield",
        name: "permissions-accordion",
      },
      items: [
        {
          component: "StartPermission",
          config: {
            label: "Permission To Start",
            helper: "Select who may start a Request of this Process",
            userHelper: "Select who may start a Request",
            groupHelper: "Select the group from which any user may start a Request",
            name: "startPermission",
          },
        },
      ],
    });
    registerInspectorExtension(startEvent, {
      component: "Interstitial",
      config: {
        label: "Display Next Assigned Task to Task Assignee",
        helper: "Directs Task assignee to the next assigned Task",
        name: "interstitial",
        enabledByDefault: true,
      },
    });

    /* Register the inspector extensions for tasks */
    registerInspectorExtension(task, {
      component: "ModelerScreenSelect",
      config: {
        label: "Screen for Input",
        helper: "Select Screen to display this Task",
        name: "screenRef",
        required: true,
        params: {
          type: "FORM,CONVERSATIONAL",
          interactive: true,
        },
      },
    });

    registerInspectorExtension(task, {
      component: "TaskDueIn",
      config: {
        label: "Due In",
        helper: "Time when the task will be due",
        name: "taskDueIn",
      },
    });
    registerInspectorExtension(task, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Assignment Rules"),
        icon: "users",
        name: "assignments-accordion",
      },
      items: [
        {
          component: "TaskAssignment",
          config: {
            label: "Assignment Type",
            helper: "",
            name: "taskAssignment",
          },
        },
      ],
    });
    registerInspectorExtension(task, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Notifications"),
        icon: "bell",
        name: "notifications-accordion",
      },
      items: [
        {
          component: "TaskNotifications",
          config: {
            helper: "Users that should be notified about task events",
          },
        },
      ],
    });

    registerInspectorExtension(task, {
      component: "Interstitial",
      config: {
        label: "Display Next Assigned Task to Task Assignee",
        helper: "Directs Task assignee to the next assigned Task",
        name: "interstitial",
      },
    });

    /* Register the inspector extensions for script tasks */
    registerInspectorExtension(scriptTask, {
      component: "ScriptSelect",
      config: {
        label: "Script",
        helper: "Select the Script this element runs",
        name: "scriptRef",
        required: true,
      },
    });

    registerInspectorExtension(scriptTask, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Error Handling"),
        icon: "exclamation-triangle",
        name: "error-handling-accordion",
      },
      items: [
        {
          component: "ErrorHandlingTimeout",
          config: {
            type: "script",
          },
        },
        {
          component: "ErrorHandlingRetryAttempts",
          config: {
            type: "script",
          },
        },
        {
          component: "ErrorHandlingRetryWaitTime",
          config: {
            type: "script",
          },
        },
        {
          component: "NotifyProcessManager",
          config: {
            type: "script",
          },
        },
      ],
    });

    registerInspectorExtension(scriptTask, {
      component: "ConfigEditor",
      config: {
        label: "Script Configuration",
        helper: "Enter the JSON to configure the Script",
        name: "config",
      },
    });
    registerInspectorExtension(endEvent, {
      component: "ModelerScreenSelect",
      config: {
        label: "Summary Screen",
        helper:
          "Select Display-type Screen to show the summary of this Request when it completes",
        name: "screenRef",
        params: { type: "DISPLAY" },
      },
    });
    registerInspectorExtension(signalEndEvent, {
      component: "ModelerScreenSelect",
      config: {
        label: "Summary Screen",
        helper:
          "Select Display-type Screen to show the summary of this Request when it completes",
        name: "screenRef",
        params: { type: "DISPLAY" },
      },
    });
    registerInspectorExtension(terminateEndEvent, {
      component: "ModelerScreenSelect",
      config: {
        label: "Summary Screen",
        helper:
          "Select Display-type Screen to show the summary of this Request when it completes",
        name: "screenRef",
        params: { type: "DISPLAY" },
      },
    });
    registerInspectorExtension(manualTask, {
      component: "ModelerScreenSelect",
      config: {
        label: "Screen for Input",
        helper:
          "Select Screen to display this Task",
        name: "screenRef",
        params: { type: "DISPLAY" },
        required: true,
      },
    });
    registerInspectorExtension(manualTask, {
      component: "TaskDueIn",
      config: {
        label: "Due In",
        helper: "Enter the hours until this Task is overdue",
        name: "taskDueIn",
      },
    });
    registerInspectorExtension(manualTask, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Assignment Rules"),
        icon: "users",
        name: "assignments-accordion",
      },
      items: [
        {
          component: "TaskAssignment",
          config: {
            label: "Task Assignment",
            helper: "",
            name: "taskAssignment",
          },
        },
      ],
    });
    registerInspectorExtension(manualTask, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Notifications"),
        icon: "bell",
        name: "notifications-accordion",
      },
      items: [
        {
          component: "TaskNotifications",
          config: {
            helper: "Users that should be notified about task events",
          },
        },
      ],
    });
    registerInspectorExtension(manualTask, {
      component: "Interstitial",
      config: {
        label: "Enable Interstitial",
        helper: "redirected to my next assigned task",
        name: "interstitial",
      },
    });

    /* Register extension for intermediate message catch event */
    registerInspectorExtension(intermediateMessageCatchEvent, {
      component: "UserSelect",
      config: {
        label: "Allowed User",
        helper: "Select allowed user",
        name: "allowedUsers",
      },
    });

    registerInspectorExtension(intermediateMessageCatchEvent, {
      component: "GroupSelect",
      config: {
        label: "Allowed Group",
        helper: "Select allowed group",
        name: "allowedGroups",
      },
    });

    registerInspectorExtension(intermediateMessageCatchEvent, {
      component: "FormInput",
      config: {
        label: i18next.t("Whitelist"),
        helper: i18next.t("IP/Domain whitelist"),
        name: "whitelist",
      },
    });

    registerInspectorExtension(sequenceFlow, {
      component: "GatewayFlowVariable",
      config: {
        label: "Screen for Input",
        helper: "Select Screen to display this Task",
        name: "FlowVariable",
      },
    });

    registerInspectorExtension(callActivity, {
      component: "FormAccordion",
      container: true,
      config: {
        initiallyOpen: false,
        label: i18next.t("Assignment Rules"),
        icon: "users",
        name: "assignments-accordion",
      },
      items: [
        {
          component: "TaskAssignment",
          config: {
            label: "Start Sub Process As",
            helper: "",
            name: "taskAssignment",
            configurables: [],
            assignmentTypes: [
              {
                value: "",
                label: "Anonymous",
              },
              {
                value: "requester",
                label: "Requester",
              },
              {
                value: "user_group",
                label: "Users / Groups",
              },
              {
                value: "previous_task_assignee",
                label: "Previous Task Assignee",
              },
              {
                value: "process_variable",
                label: "Process Variable",
              },
            ],
          },
        },
      ],
    });

    registerInspectorExtension(intermediateSignalThrowEvent, {
      component: "SignalPayload",
      config: {
        label: "Payload Type",
        helper: "data that will be sent as payload",
        name: "interstitial",
      },
    });

    registerInspectorExtension(signalEndEvent, {
      component: "SignalPayload",
      config: {
        label: "Payload Type",
        helper: "data that will be sent as payload",
        name: "interstitial",
      },
    });
  },
);

ProcessMaker.EventBus.$on(
  "modeler-init",
  (event) => {
    event.registerPreview({
      url: '/designer/screens/preview',
      assetUrl: (nodeData) => nodeData.screenRef ? `/designer/screen-builder/${nodeData.screenRef}/edit` : null,
      receivingParams: ['screenRef'],
      matcher: (nodeData) => nodeData?.$type === 'bpmn:Task',
    });
    event.registerPreview({
      url: '/designer/screens/preview',
      assetUrl: (nodeData) => nodeData.screenRef ? `/designer/screen-builder/${nodeData.screenRef}/edit` : null,
      receivingParams: ['screenRef'],
      matcher: (nodeData) => nodeData?.$type === 'bpmn:ManualTask',
    });
    event.registerPreview({
      url: '/designer/scripts/preview',
      assetUrl: (nodeData) => nodeData.scriptRef ? `/designer/scripts/${nodeData.scriptRef}/builder` : null,
      receivingParams: ['scriptRef'],
      matcher: (nodeData) => nodeData?.$type === 'bpmn:ScriptTask',
    });
  });

validateScreenRef();
validateFlowGenieRef();
