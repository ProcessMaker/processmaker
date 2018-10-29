import Vue from "vue";
import ModelerApp from "./components/ModelerApp";

import * as VueDeepSet from "vue-deepset";

Vue.use(VueDeepSet);

new Vue({
    render: h => h(ModelerApp)
}).$mount("#modeler-app");
