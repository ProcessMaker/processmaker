import Vue from "vue";
import ImportManagerView from "./components/ImportManagerView.vue";
import ProcessDetailConfigs from "./components/ProcessDetailConfigs.vue";
import State from "../export/state";
import CustomExportView from "../export/components/CustomExportView.vue";

const routes = [
  {
    path: "/processes/import",
    name: "main",
    component: ImportManagerView,
  },
  {
    path: "/processes/import/custom",
    name: "custom",
    component: CustomExportView,
  },
  {
    path: "/processes/import/new-process",
    name: "import-new-process",
    component: ProcessDetailConfigs,
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
