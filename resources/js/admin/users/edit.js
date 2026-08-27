import Vue from "vue";
import VuePassword from "vue-password";
import UserGroupsListing from "./components/UserGroupsListing.vue";
import UserTokensListing from "./components/UserTokensListing.vue";
import SecurityLogsListing from "./components/SecurityLogsListing.vue";

Vue.component("VuePassword", VuePassword);
Vue.component("UserGroupsListing", UserGroupsListing);
Vue.component("UserTokensListing", UserTokensListing);
Vue.component("SecurityLogsListing", SecurityLogsListing);
