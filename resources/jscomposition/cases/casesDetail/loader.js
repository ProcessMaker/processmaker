import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";
import ScreenBuilder from "@processmaker/screen-builder";
import {
  setUses, setGlobalVariables, setGlobalPMVariables,
} from "../../../js/next/globalVariables";

import vuex from "../../../js/next/libraries/vuex";
// import lodash from "../../../js/next/libraries/lodash";
import bootstrap from "../../../js/next/libraries/bootstrap";
import jquery from "../../../js/next/libraries/jquery";
import vueRouter from "../../../js/next/libraries/vueRouter";
import vueCookies from "../../../js/next/libraries/vueCookies";

import processmaker from "../../../js/next/config/processmaker";
import broadcast from "../../../js/next/libraries/broadcast";
import i18n from "../../../js/next/config/i18n";
import notifications from "../../../js/next/config/notifications";
import user from "../../../js/next/config/user";
import session from "../../../js/next/config/session";
import openAI from "../../../js/next/config/openAI";

// Load syncronously shared components in window, for some packages
import sharedComponents from "../../../js/next/libraries/sharedComponents";
import vueFormElements from "../../../js/next/libraries/vueFormElements";
import modelerInspector from "../../../js/next/libraries/modelerInspector";
import modeler from "../../../js/next/modeler";
import screenBuilderNext from "../../../js/next/screenBuilder";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;
window.ScreenBuilder = ScreenBuilder;

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

// Initialize components asyncronously with Vue.component
import("../../../js/next/components/index");
import("../../../js/next/config/accesibility");

// Initialize screenBuilder
screenBuilderNext({ global: window });

const modelerConfig = modeler;
setGlobalPMVariables(modelerConfig.pm);

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

// Layout modules asyncronously
import("../../../js/next/layout/sidebar");
import("../../../js/next/layout/navbar");
