import Vue from "vue";
import RequestDetail from "./components/RequestDetail";
import AvatarImage from "../components/AvatarImage";
import moment from "moment"

Vue.component('request-detail', RequestDetail);
Vue.component('avatar-image', AvatarImage);

Vue.prototype.moment = moment;
