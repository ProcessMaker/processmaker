import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";
import {
  setUses, setGlobalVariables, setGlobalPMVariables,
} from "./globalVariables";

import vuex from "./libraries/vuex";
import bootstrap from "./libraries/bootstrap";
import jquery from "./libraries/jquery";
import lodash from "./libraries/lodash";
import sharedComponents from "./libraries/sharedComponents";
import asyncComponents from "./components/index";
import ProcessesComponents from "./libraries/processesComponents";
import ProcessesCatalogueComponents from "./libraries/processesCatalogueComponents";
import ScriptsComponents from "./libraries/scriptsComponents";
import ScreensComponents from "./libraries/screensComponents";

import vueRouter from "./libraries/vueRouter";
import vueCookies from "./libraries/vueCookies";
import processmaker from "./config/processmaker";
import broadcast from "./libraries/broadcast";
import i18n from "./config/i18n";
import notifications from "./config/notifications";
import user from "./config/user";
import session from "./config/session";
import openAI from "./config/openAI";

export const setupMain = () => {
  window.Vue = Vue;
  window.vue = vue;
  window.moment = moment;

  window.Vue.prototype.moment = moment;
  window.Vue.use(BootstrapVue);
  window.Vue.use(BootstrapVueIcons);

  window.ProcessMaker = {
    EventBus: new Vue(),
    events: new Vue(),
    packages: window.packages,
  };

  // Vuex
  setUses(Vue, vuex.use);

  // Bootstrap
  setGlobalVariables(bootstrap.global);

  // Jquery
  setGlobalVariables(jquery.global);

  // VueRouter
  setGlobalVariables(vueRouter.global);
  setGlobalPMVariables(vueRouter.pm);
  setUses(Vue, vueRouter.use);

  // VueCookies
  setUses(Vue, vueCookies.use);

  // INIT CONFIGURATION
  processmaker();
  broadcast();
  i18n();
  notifications();
  user();
  session();
  openAI();
  asyncComponents();

  // Initialize components asyncronously
  import("./config/accesibility");
  import("./layout/sidebar");
  import("./layout/navbar");
};

export default { };
