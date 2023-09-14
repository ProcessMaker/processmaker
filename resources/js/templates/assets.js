import Vue from "vue";
import Router from "vue-router";
import TemplateAssetsView from "../components/templates/TemplateAssetsView.vue";
import TemplateAssetsManager from "../components/templates/TemplateAssetsManager.vue";

Vue.component("TemplateAssetsView", TemplateAssetsView);

const routes = [
  {
    path: "/template/assets",
    name: "choose-template-assets",
    component: TemplateAssetsView,
    props: route => ({
      routeName: "choose-template-assets",
      existingAssets: route.params.existingAssets,
    }),
  },

];

const router = new Router({
  routes,
});

new Vue({
  components: { TemplateAssetsManager },
  mixins: [],
  router: window.ProcessMaker.Router,
  data() {
    return {
    };
  },
  beforeMount() {
    this.$router.addRoutes(routes);
  },
}).$mount("#template-asset-manager");

export default router;
