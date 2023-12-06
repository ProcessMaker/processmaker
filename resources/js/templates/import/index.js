import Vue from "vue";
import ImportManagerView from "../../processes/import/components/ImportManagerView.vue";
import TemplateDetailConfigs from "./components/TemplateDetailConfigs.vue";
import State from "../../processes/export/state";

const assetType = document.head.querySelector("meta[name=\"import-template-asset-type\"]").content;

const routes = [
  {
    path: "/template/:assetType/import",
    name: "main",
    component: ImportManagerView,
    props: (route) => ({
      routeName: "main",
      assetType,
    }),
  },
  {
    path: "/template/import/new-template",
    name: "import-new-template",
    component: TemplateDetailConfigs,
  },
];

new Vue({
  components: { },
  mixins: [State],
  router: window.ProcessMaker.Router,
  data() {
    return {
    };
  },
  watch: {
    $route: {
      handler() {
        // TODO: Add handlers route changes such as breadcrumb updates etc..
      },
    },
  },
  beforeMount() {
    this.$root.isImport = true;
    this.$router.addRoutes(routes);
  },
}).$mount("#import-manager");
