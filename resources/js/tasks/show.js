import Vue from "vue";
import Vuex from "vuex";
import Task from "@processmaker/screen-builder";
import MonacoEditor from "vue-monaco";
import debounce from "lodash/debounce";
import TaskView from "./components/TaskView.vue";
import NavbarTaskMobile from "./components/NavbarTaskMobile.vue";
import AvatarImage from "../components/AvatarImage.vue";
import Timeline from "../components/Timeline.vue";
import TimelineItem from "../components/TimelineItem.vue";
import TaskSavePanel from "./components/TaskSavePanel.vue";
import autosaveMixins from "../modules/autosave/autosaveMixin";

Vue.use(Vuex);
Vue.use("task", Task);
Vue.component("TaskView", TaskView);
Vue.component("NavbarTaskMobile", NavbarTaskMobile);
Vue.component("AvatarImage", AvatarImage);
Vue.component("MonacoEditor", MonacoEditor);
Vue.component("Timeline", Timeline);
Vue.component("TimelineItem", TimelineItem);
Vue.component("TaskSavePanel", TaskSavePanel);

Vue.mixin(autosaveMixins);

window.debounce = debounce;
window.Vuex = Vuex;
