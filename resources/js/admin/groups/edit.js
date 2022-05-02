import Vue from "vue";
import UsersInGroupListing from "./components/UsersInGroupListing.vue";
import GroupsInGroupListing from "./components/GroupsInGroupListing.vue";
import UserSelect from "../../processes/modeler/components/inspector/UserSelect.vue";

Vue.component("UsersInGroup", UsersInGroupListing);
Vue.component("GroupsInGroup", GroupsInGroupListing);
Vue.component("UserSelect", UserSelect);
