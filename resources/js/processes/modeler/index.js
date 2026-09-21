import Vue from "vue";
import ModelerApp from "./components/ModelerApp";

// Mount after 'load' so package addon scripts (from $manager->getScriptWithParams())
// are guaranteed to have executed before Vue renders the modeler.
window.addEventListener("load", () => {
  window.ProcessMaker.i18nPromise.then(() => {
    new Vue({
      render: (h) => h(ModelerApp, {
        props: {
          showToolbar: window.document.getElementById("modeler-app").getAttribute("show-toolbar") !== "false",
        },
      }),
    }).$mount("#modeler-app");
  });
});

