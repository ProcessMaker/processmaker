import Vue from "vue";
import ProcessMap from "./components/ProcessMap";

window.ProcessMaker.i18nPromise.then(() => {
  new Vue({
    render: (h) => h(ProcessMap),
  }).$mount("#modeler-app");
});
