import Vue from "vue";
import VuePassword from "vue-password";
import { PTab, PTabs } from "../../components/shared";
import ExportManager from "./components/ExportManager.vue";
import ExportManagerView from "./components/ExportManagerView.vue";
import CustomExportView from "./components/CustomExportView.vue";
import ProcessesView from "./components/process-elements/ProcessesView.vue";
import ScriptsView from "./components/process-elements/ScriptsView.vue";

Vue.component("PTab", PTab);
Vue.component("PTabs", PTabs);
Vue.component("VuePassword", VuePassword);

const processName = document.head.querySelector("meta[name=\"export-process-name\"]").content;

const routes = [
  {
    path: "/processes/:processId/export",
    name: "main",
    component: ExportManagerView,
    props: route => ({
      processId: route.params.processId,
      routeName: "main",
      processName: processName,
    }),
  },
  {
    path: "/processes/:processId/export/custom",
    name: "export-custom-process",
    component: CustomExportView,
    children: [
      {
        path: "/processes/:processId/export/custom",
        component: PTabs,
        children: [{
          path: "/processes/:processId/export/custom",
          component: PTab,
          children: [{
            path: "",
            components: {
              default: ProcessesView,
            },
          }],
        }],
      },
    //   {
    //     path: "scripts",
    //     component: ScriptsView,
    //   },
    ],
    props: route => ({
      routeName: "export-custom-process",
      processName: processName,
      processId: route.params.processId,
    }),
  },
];

new Vue({
  router: window.ProcessMaker.Router,
  components: { ExportManager },
  data() {
    return {
    }
  },
  watch: {
    $route: {
      handler() {
        // TODO: Add handlers route changes such as breadcrumb updates etc..
      },
    },
  },
  beforeMount() {
    // this.$router.addRoutes(routes);
  },
}).$mount("#export-manager");
