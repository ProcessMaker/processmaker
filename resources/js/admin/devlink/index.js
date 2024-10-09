import Vue from "vue";
import DevLink from "./components/DevLink";
import Index from "./components/Index";
import Instance from "./components/Instance";
import LocalBundles from "./components/LocalBundles";
import Assets from "./components/Assets";
import AssetListing from "./components/AssetListing";
import SharedAssets from "./components/SharedAssets";

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
    {
      name: 'assets',
      path: '/instance/:id/assets',
      component: Assets,
    },
    {
      name: 'asset-listing',
      path: '/instance/:id/assets/:type',
      component: AssetListing,
    },
    {
      name: 'assets-shared',
      path: '/assets-shared',
      component: SharedAssets,
    }
  ]
});

new Vue({
  el: "#devlink",
  router,
  components: { DevLink },
});
