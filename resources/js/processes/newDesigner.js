import Vue from "vue";
import Assets from "./designer/Assets";
import RecentAssets from "./designer/RecentAssets";
import MyProject from "./designer/MyProject";

new Vue({
  el: "#new-designer",
  components: {
    Assets,
    RecentAssets,
    MyProject,
  },
});
