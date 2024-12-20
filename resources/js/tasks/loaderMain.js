import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";
import {
  setUses, setGlobalVariables, setGlobalPMVariables,
} from "../next/globalVariables";

import vuex from "../next/libraries/vuex";
import lodash from "../next/libraries/lodash";
import bootstrap from "../next/libraries/bootstrap";
import jquery from "../next/libraries/jquery";
import vueRouter from "../next/libraries/vueRouter";
import vueCookies from "../next/libraries/vueCookies";

import processmaker from "../next/config/processmaker";
import broadcast from "../next/libraries/broadcast";
import i18n from "../next/config/i18n";
import notifications from "../next/config/notifications";
import user from "../next/config/user";
import session from "../next/config/session";
import openAI from "../next/config/openAI";

// Load syncronously shared components in window, for some packages
import sharedComponents from "../next/libraries/sharedComponents";
import vueFormElements from "../next/libraries/vueFormElements";

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

import("../next/components/index");
import("../next/screenBuilder");

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

const processmakerConfig = processmaker(window.ProcessMaker);
setGlobalPMVariables(processmakerConfig.pm);

const broadcastConfig = broadcast(window.ProcessMaker);
setGlobalVariables(broadcastConfig.global);

const i18nConfig = i18n(window.ProcessMaker);
setUses(Vue, i18nConfig.use);
Vue.mixin({ i18n: new i18nConfig.use.VueI18Next(i18nConfig.pm.i18n) });
setGlobalPMVariables(i18nConfig.pm);

const notificationsConfig = notifications(window.ProcessMaker);
setGlobalPMVariables(notificationsConfig.pm);

const userConfig = user({ global: window });
setGlobalPMVariables(userConfig.pm);

const sessionConfig = session({ global: window, processmaker: window.ProcessMaker });
setGlobalPMVariables(sessionConfig.pm);

const openAIConfig = openAI();
setGlobalPMVariables(openAIConfig.pm);

// Layout modules
import("../next/layout/sidebar");
import("../next/layout/navbar");
