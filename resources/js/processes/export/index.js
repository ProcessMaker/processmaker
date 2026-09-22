import Vue from "vue";
import VuePassword from "vue-password";
import ExportManager from "./components/ExportManager.vue";
import ExportManagerView from "./components/ExportManagerView.vue";
import CustomExportView from "./components/CustomExportView.vue";
import State from "./state";

Vue.component("VuePassword", VuePassword);

const processName = document.head.querySelector('meta[name="export-process-name"]').content;
const projectId = document.head.querySelector('meta[name="export-project-id"]').content;

const routes = [
  {
    path: "/processes/:processId/export",
    name: "main",
    component: ExportManagerView,
    props: (route) => ({
      processId: route.params.processId,
      routeName: "main",
      processName,
    }),
  },
  {
    path: "/processes/:processId/export/custom",
    name: "export-custom-process",
    component: CustomExportView,
    props: (route) => ({
      routeName: "export-custom-process",
      processName,
      processId: route.params.processId,
      projectId,
    }),
  },
];

new Vue({
  mixins: [State],
  router: window.ProcessMaker.Router,
  components: { ExportManager },
  data() {
    return {};
  },
  beforeMount() {
    this.$router.addRoutes(routes);
  },
  watch: {
    $route: {
      handler() {
        // TODO: Add handlers route changes such as breadcrumb updates etc..
      },
    },
  },
}).$mount("#export-manager");
