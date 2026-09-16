import Vue from "vue";
import Assets from "./designer/Assets.vue";
import RecentAssets from "./designer/RecentAssets.vue";
import MyProject from "./designer/MyProject.vue";
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
