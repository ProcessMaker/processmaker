import Vue from "vue";
import * as ScreenBuilder from "@processmaker/screen-builder";
import "@processmaker/screen-builder/dist/vue-form-builder.css";
import SettingsGroups from "./components/SettingsGroups.vue";
import SettingsMain from "./components/SettingsMain.vue";

window.ScreenBuilder = ScreenBuilder;
window.Vue.use(ScreenBuilder.default);

new Vue({
  el: "#settings",
  components: { SettingsGroups, SettingsMain },
});
