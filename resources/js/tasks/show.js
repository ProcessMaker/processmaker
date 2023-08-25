import Vue from "vue";
import Vuex from "vuex";
import Task from "@processmaker/screen-builder";
import MonacoEditor from "vue-monaco";
import debounce from "lodash/debounce";
import TaskView from "./components/TaskView.vue";
import AvatarImage from "../components/AvatarImage.vue";
import Timeline from "../components/Timeline.vue";
import TimelineItem from "../components/TimelineItem.vue";

Vue.use(Vuex);
Vue.use("task", Task);
Vue.component("TaskView", TaskView);
Vue.component("AvatarImage", AvatarImage);
Vue.component("MonacoEditor", MonacoEditor);
Vue.component("Timeline", Timeline);
Vue.component("TimelineItem", TimelineItem);

window.debounce = debounce;
window.Vuex = Vuex;
