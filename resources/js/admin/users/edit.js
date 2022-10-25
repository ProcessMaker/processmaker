import Vue from "vue";
import VuePassword from "vue-password";
import UserGroupsListing from "./components/UserGroupsListing";
import UserTokensListing from "./components/UserTokensListing";
import SecurityLogsListing from "./components/SecurityLogsListing";

Vue.component("VuePassword", VuePassword);
Vue.component("UserGroupsListing", UserGroupsListing);
Vue.component("UserTokensListing", UserTokensListing);
Vue.component("SecurityLogsListing", SecurityLogsListing);
