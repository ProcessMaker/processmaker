import Vue from "vue";
import SettingsGroups from "./components/SettingsGroups.vue";
import SettingsMain from "./components/SettingsMain.vue";

new Vue({
  el: "#settings",
  components: { SettingsGroups, SettingsMain },
});
