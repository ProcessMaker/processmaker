import Vue from "vue";
import Vuex from "vuex";
import Task from "@processmaker/screen-builder";
import MonacoEditor from "vue-monaco";
import debounce from "lodash/debounce";
import QuickFillPreview from "../tasks/components/QuickFillPreview.vue";
import TasksList from "../tasks/components/TasksList.vue";
import TasksPreview from "../tasks/components/TasksPreview.vue";


Vue.use(Vuex);
Vue.use("task", Task);
Vue.component("MonacoEditor", MonacoEditor);
Vue.component("QuickFillPreview", QuickFillPreview);
Vue.component("TasksList", TasksList);
Vue.component("TasksPreview", TasksPreview);

window.debounce = debounce;
window.Vuex = Vuex;