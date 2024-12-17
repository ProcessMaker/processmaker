import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";
import { loadModulesSequentially } from "../../../js/next/globalVariables";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;

window.Vue.prototype.moment = moment;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);

import("../../../js/next/libraries/vuex");
import("../../../js/next/libraries/broadcast");
import("../../../js/next/libraries/bootstrap");
import("../../../js/next/libraries/jquery");
import("../../../js/next/libraries/vueRouter");
import("../../../js/next/libraries/vueCookies");
// Load components
import("../../../js/next/components/index");

window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue(),
};

loadModulesSequentially([
  import("../../../js/next/config/processmaker"),
  import("../../../js/next/config/i18n"), // $t is important when the blade related is huge
]);

// import("../../../js/next/config/processmaker");
import("../../../js/next/config/notifications");
// import("../../../js/next/config/i18n");
import("../../../js/next/config/user");
import("../../../js/next/config/session");
// import("../../../js/next/config/momentConfig");
import("../../../js/next/config/openAI");

// Screen builder
import("../../../js/next/screenBuilder");

// Load libraries dependencies first
import("../../../js/next/layout/sidebar");
import("../../../js/next/layout/navbar");
