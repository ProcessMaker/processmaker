import Vue from "vue";
import VuePassword from "vue-password";
import { PTab, PTabs } from "../../components/shared";
import ExportManager from "./components/ExportManager.vue";
import ExportManagerView from "./components/ExportManagerView.vue";
import CustomExportView from "./components/CustomExportView.vue";
import ProcessesView from "./components/process-elements/ProcessesView.vue";
import ScriptsView from "./components/process-elements/ScriptsView.vue";
import PmContainer from "./components/PmContainer.vue";
import PmContent from "./components/PmContent.vue";

Vue.component("PTab", PTab);
Vue.component("PTabs", PTabs);
Vue.component("VuePassword", VuePassword);

const processName = 123;

const routes = [
  // {
  //   path: "/processes/nav",
  //   component: CustomExportView,
  // },
];

new Vue({
  router: window.ProcessMaker.Router,
  components: { PmContainer, PmContent },
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
    // this.$router.addRoute(routes);
    // routes.forEach((route) => this.$router.addRoute(route));
  },
}).$mount("#nav-test");
