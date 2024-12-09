import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";

import AvatarImage from "../../../js/components/AvatarImage.vue";
import TimelineItem from "../../../js/components/TimelineItem.vue";
import SelectUserGroup from "../../../js/components/SelectUserGroup.vue";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;

window.Vue.prototype.moment = moment;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);

import("../../../js/next/libraries/broadcast");
import("../../../js/next/libraries/vuex");
import("../../../js/next/libraries/bootstrap");
import("../../../js/next/libraries/jquery");
import("../../../js/next/libraries/vueRouter");
import("../../../js/next/libraries/vueCookies");
import("../../../js/next/libraries/vueFormElements");

window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue(),
};

import("../../../js/next/config/processmaker");
import("../../../js/next/config/i18n");
import("../../../js/next/config/user");
import("../../../js/next/config/session");
import("../../../js/next/config/momentConfig");
import("../../../js/next/config/notifications");
import("../../../js/next/config/modals");
import("../../../js/next/config/openAI");

// Screen builder
// import("../../../js/nextDependencies/screenBuilder");

// Load libraries dependencies first
import("../../../js/next/layout/sidebar");
import("../../../js/next/layout/navbar");

Vue.component("AvatarImage", AvatarImage);
Vue.component("TimelineItem", TimelineItem);
Vue.component("SelectUserGroup", SelectUserGroup);
