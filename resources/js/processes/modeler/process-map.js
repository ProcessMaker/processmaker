import Vue from "vue";
// eslint-disable-next-line import/no-extraneous-dependencies
import panZoom from "vue-panzoom";
import ProcessMap from "./components/ProcessMap.vue";

Vue.use(panZoom);
window.ProcessMaker.i18nPromise.then(() => {
  new Vue({
    render: (h) => h(ProcessMap),
  }).$mount("#modeler-app");
});
