import Vue from "vue";
import Assets from "./designer/Assets";
import RecentAssets from "./designer/RecentAssets";
import MyProject from "./designer/MyProject";
import WelcomeDesigner from "./designer/WelcomeDesigner.vue";

new Vue({
  el: "#new-designer",
  components: {
    Assets,
    RecentAssets,
    MyProject,
    WelcomeDesigner,
  },
});
