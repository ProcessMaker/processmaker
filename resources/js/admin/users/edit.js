import Vue from "vue";
import VuePassword from "vue-password";
import GroupsListing from './components/GroupsListing';
import UserGroupsListing from './components/UserGroupsListing';
import UserTokensListing from './components/UserTokensListing';

Vue.component("vue-password", VuePassword);
Vue.component("groups-listing", GroupsListing);
Vue.component("user-groups-listing", UserGroupsListing);
Vue.component("user-tokens-listing", UserTokensListing);
