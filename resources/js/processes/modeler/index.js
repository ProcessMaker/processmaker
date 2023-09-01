import Vue from "vue";
import Vuex from "vuex";
import ModelerApp from "./components/ModelerApp";

Vue.use(Vuex);

const store = new Vuex.Store({

});

window.ProcessMaker.i18nPromise.then(() => {
  new Vue({
    store,
    render: (h) => h(ModelerApp),
  }).$mount("#modeler-app");
});
