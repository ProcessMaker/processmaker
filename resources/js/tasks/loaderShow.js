import Vue from "vue";
import * as vue from "vue";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import moment from "moment-timezone";

import { Multiselect } from "@processmaker/vue-multiselect";
import AvatarImage from "../components/AvatarImage.vue";
import TimelineItem from "../components/TimelineItem.vue";
import SelectUserGroup from "../components/SelectUserGroup.vue";
import PmqlInput from "../components/shared/PmqlInput.vue";
import FilterTable from "../components/shared/FilterTable.vue";
import PaginationTable from "../components/shared/PaginationTable.vue";
import PMDropdownSuggest from "../components/PMDropdownSuggest.vue";
import Required from "../components/shared/Required.vue";

window.Vue = Vue;
window.vue = vue;
window.moment = moment;

window.Vue.prototype.moment = moment;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);

import("../next/libraries/vuex");
import("../next/libraries/broadcast");
import("../next/libraries/bootstrap");
import("../next/libraries/jquery");
import("../next/libraries/vueRouter");
import("../next/libraries/vueCookies");

window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue(),
};

import("../next/config/processmaker");
import("../next/config/notifications");
import("../next/config/i18n");
import("../next/config/user");
import("../next/config/session");
import("../next/config/momentConfig");
import("../next/config/openAI");

// Load components
import("../next/libraries/vueFormElements");
import("../next/libraries/sharedComponents");

// Screen builder
import("../next/screenBuilder");

// Load libraries dependencies first
import("../next/layout/sidebar");
import("../next/layout/navbar");

Vue.component("AvatarImage", AvatarImage);
Vue.component("TimelineItem", TimelineItem);
Vue.component("SelectUserGroup", SelectUserGroup);
Vue.component("PmqlInput", PmqlInput);
Vue.component("FilterTable", FilterTable);
Vue.component("PaginationTable", PaginationTable);
Vue.component("PMDropdownSuggest", PMDropdownSuggest);
Vue.component("Required", Required);
Vue.component("Multiselect", Multiselect);
