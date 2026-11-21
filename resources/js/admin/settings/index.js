import Vue from "vue";
import * as ScreenBuilder from "@processmaker/screen-builder";
import "@processmaker/screen-builder/dist/vue-form-builder.css";
import SettingsGroups from "./components/SettingsGroups.vue";
import SettingsMain from "./components/SettingsMain.vue";
import McpSkills from "./components/McpSkills.vue";

window.ScreenBuilder = ScreenBuilder;
window.Vue.use(ScreenBuilder.default);
window.__setting_component_McpSkills = McpSkills;

new Vue({
  el: "#settings",
  components: { SettingsGroups, SettingsMain },
});
