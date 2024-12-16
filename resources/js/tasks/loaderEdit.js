import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";

import { Multiselect } from "@processmaker/vue-multiselect";
import debounce from "lodash/debounce";
import Mustache from "mustache";
// import Task from "@processmaker/screen-builder";
import { loadModulesSequentially } from "../next/globalVariables";

import AvatarImage from "../components/AvatarImage.vue";
import SelectUserGroup from "../components/SelectUserGroup.vue";
import PmqlInput from "../components/shared/PmqlInput.vue";
import FilterTable from "../components/shared/FilterTable.vue";
import PaginationTable from "../components/shared/PaginationTable.vue";
import PMDropdownSuggest from "../components/PMDropdownSuggest.vue";
import Required from "../components/shared/Required.vue";
import DataTreeToggle from "../components/common/data-tree-toggle.vue";
import TreeView from "../components/TreeView.vue";
import TaskView from "./components/TaskView.vue";
import TasksPreview from "./components/TasksPreview.vue";

import NavbarTaskMobile from "./components/NavbarTaskMobile.vue";
import Timeline from "../components/Timeline.vue";
import TimelineItem from "../components/TimelineItem.vue";
import QuickFillPreview from "./components/QuickFillPreview.vue";
import TasksList from "./components/TasksList.vue";

import TaskSavePanel from "./components/TaskSavePanel.vue";
import autosaveMixins from "../modules/autosave/autosaveMixin";
import draftFileUploadMixin from "../modules/autosave/draftFileUploadMixin";
import TaskSaveNotification from "./components/TaskSaveNotification.vue";
import reassignMixin from "../common/reassignMixin";
import ReassignMobileModal from "./components/ReassignMobileModal.vue";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;

window.Vue.prototype.moment = moment;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);

window.debounce = debounce;
window.Mustache = Mustache;

window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue(),
  packages: window.packages,
};

loadModulesSequentially([
  import("../next/libraries/vuex"),
  import("../next/libraries/bootstrap"),
  import("../next/libraries/jquery"),
  import("../next/libraries/vueCookies"),
  import("../next/config/i18n"),
  import("../next/libraries/vueRouter"),
  import("../next/libraries/broadcast"),
  import("../next/config/processmaker"),
  import("../next/config/notifications"),
  import("../next/config/user"),
  import("../next/config/session"),
  import("../next/config/momentConfig"),
  import("../next/config/openAI"),
  // Load components
  import("../next/libraries/vueFormElements"), // Necessary for many packages for the screen builder
  // import("../next/libraries/sharedComponents");

  // Screen builder
  import("../next/screenBuilder"),
  import("../next/monaco"),

  // Load libraries dependencies first
  import("../next/layout/sidebar"),
  import("../next/layout/navbar"),
  import("./edit"),
])
  .then((modules) => {
    console.log(modules);
  })
  .catch((error) => {
    console.error("Error al cargar los módulos:", error);
  });

Vue.component("SelectUserGroup", SelectUserGroup);
Vue.component("PmqlInput", PmqlInput);
Vue.component("FilterTable", FilterTable);
Vue.component("PaginationTable", PaginationTable);
Vue.component("PMDropdownSuggest", PMDropdownSuggest);
Vue.component("Required", Required);
Vue.component("Multiselect", Multiselect);
Vue.component("DataTreeToggle", DataTreeToggle);
Vue.component("TreeView", TreeView);
Vue.component("TaskView", TaskView);
Vue.component("TaskPreview", TasksPreview);
Vue.component("TaskView", TaskView);
Vue.component("NavbarTaskMobile", NavbarTaskMobile);
Vue.component("AvatarImage", AvatarImage);
Vue.component("Timeline", Timeline);
Vue.component("TimelineItem", TimelineItem);
Vue.component("QuickFillPreview", QuickFillPreview);
Vue.component("TasksList", TasksList);
Vue.component("TaskSavePanel", TaskSavePanel);
// Vue.use(Task);
Vue.component("TaskSaveNotification", TaskSaveNotification);
Vue.component("ReassignMobileModal", ReassignMobileModal);
Vue.mixin(autosaveMixins);
Vue.mixin(draftFileUploadMixin);
Vue.mixin(reassignMixin);
