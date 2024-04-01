import Vue from "vue";
import ModelerApp from "./components/ModelerApp";

window.ProcessMaker.i18nPromise.then(() => {
  new Vue({
    render: (h) => h(ModelerApp, {
      props: {
        showToolbar: window.document.getElementById("modeler-app").getAttribute("show-toolbar") !== "false",
      },
    }),
  }).$mount("#modeler-app");
});
