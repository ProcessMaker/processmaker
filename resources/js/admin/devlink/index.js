import Vue from "vue";
import DevLink from "./components/DevLink";
import Index from "./components/Index";
import Instance from "./components/Instance";
import LocalBundles from "./components/LocalBundles";

Vue.use(VueRouter);
const router = new VueRouter({
  mode: "history",
  base: "/admin/devlink",
  //See https://v3.router.vuejs.org/guide/
  routes: [
    {
      name: "index",
      path: "/",
      component: Index,
    },
    {
      name: "instance",
      path: "/instance/:id",
      component: Instance,
    },
    {
      name: "local-bundles",
      path: "/local-bundles",
      component: LocalBundles,
    },
  ]
});

new Vue({
  el: "#devlink",
  router,
  components: { DevLink },
});
