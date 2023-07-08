import Vue from "vue";
import ProcessMap from "./components/ProcessMap.vue";

window.ProcessMaker.i18nPromise.then(() => {
  new Vue({
    render: (h) => h(ProcessMap),
  }).$mount("#modeler-app");
});
