import Vue from "vue";
import UsersInGroupListing from "./components/UsersInGroupListing.vue";
import GroupsInGroupListing from "./components/GroupsInGroupListing.vue";
import UserSelect from "../../processes/modeler/components/inspector/UserSelect.vue";

Vue.component('users-in-group', UsersInGroupListing);
Vue.component('groups-in-group', GroupsInGroupListing);
Vue.component('user-select', UserSelect);
