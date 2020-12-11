import Vue from "vue";
import ModelerApp from "./components/ModelerApp";

window.ProcessMaker.i18nPromise.then(() => {
    new Vue({
        render: h => h(ModelerApp)
        }).$mount("#modeler-app");
})
