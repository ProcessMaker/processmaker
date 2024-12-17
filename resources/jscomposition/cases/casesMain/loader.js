import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;

window.Vue.prototype.moment = moment;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);

// Libraries modules loaded asynchronously
import("../../../js/next/libraries/broadcast");
import("../../../js/next/libraries/vuex");
import("../../../js/next/libraries/bootstrap");
import("../../../js/next/libraries/jquery");
import("../../../js/next/libraries/vueRouter");
import("../../../js/next/libraries/vueCookies");
import("../../../js/next/components/index");

// Global variables ProcessMaker
window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue(),
  packages: window.packages,
};

// Modules loaded synchronously
import("../../../js/next/config/processmaker");
import("../../../js/next/config/i18n");
import("../../../js/next/config/notifications");
import("../../../js/next/config/user");
import("../../../js/next/config/session");
import("../../../js/next/config/openAI");

// Layout modules
import("../../../js/next/layout/sidebar");
import("../../../js/next/layout/navbar");
