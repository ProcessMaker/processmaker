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
import Router from "vue-router";

Vue.component("PTab", PTab);
Vue.component("PTabs", PTabs);
Vue.component("VuePassword", VuePassword);

const processName = 123;

new Vue({
  router: new Router({base: '/processes/nav', mode: 'history'}),
  components: { PmContainer, PmContent },
  data() {
    return {
      testSomething: 'foo',
    }
  },
  mounted() {
    setTimeout(() => {
      console.log("testing reactivity");
      this.testSomething = 'bar';
    }, 3000);
  }
}).$mount("#nav-test");
