import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";

import debounce from "lodash/debounce";
import Mustache from "mustache";

import { loadModulesSequentially } from "../next/globalVariables";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;

window.Vue.prototype.moment = moment;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);

window.debounce = debounce;
window.Mustache = Mustache;

import("../next/libraries/vuex");
import("../next/libraries/bootstrap");
import("../next/libraries/jquery");
import("../next/libraries/vueRouter");
import("../next/libraries/vueCookies");
// Load components
import("../next/components/index");
import("../next/libraries/sharedComponents");
import("../next/libraries/vueFormElements");

window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue(),
  packages: window.packages,
};

loadModulesSequentially([
  // Screen builder
  import("../next/config/processmaker"),
  import("../next/config/i18n"),
  import("../next/libraries/broadcast"),
  import("../next/config/session"),
  import("../next/screenBuilder"),
]).then(() => {
  import("./preview");
});

import("../next/config/notifications");
import("../next/config/user");
import("../next/config/momentConfig");
import("../next/config/openAI");

// Load libraries dependencies first
import("../next/layout/sidebar");
import("../next/layout/navbar");
