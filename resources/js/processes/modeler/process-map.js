import Vue from "vue";
import ProcessMap from "./components/ProcessMap.vue";

window.ProcessMaker.i18nPromise.then(() => {
  new Vue({
    render: (h) => h(ProcessMap, {
      props: {
        enableTooltip: (window.document
                          .getElementById("modeler-app")
                          .getAttribute("enable-tooltip") ?? "true") === "true",
        forDocumenting: (window.document
                          .getElementById("modeler-app")
                          .getAttribute("for-documenting") ?? "false") === "true",
      },
    }),
  }).$mount("#modeler-app");
});
