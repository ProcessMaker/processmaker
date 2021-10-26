import Vue from "vue";
import VuePassword from "vue-password";
import UserGroupsListing from './components/UserGroupsListing';
import UserTokensListing from './components/UserTokensListing';
import SecurityLogsListing from './components/SecurityLogsListing';

Vue.component("vue-password", VuePassword);
Vue.component("user-groups-listing", UserGroupsListing);
Vue.component("user-tokens-listing", UserTokensListing);
Vue.component("security-logs-listing", SecurityLogsListing);
