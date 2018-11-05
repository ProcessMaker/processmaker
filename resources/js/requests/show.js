import Vue from "vue";
import RequestDetail from "./components/RequestDetail";
import AvatarImage from "../components/AvatarImage";
import TaskForm from '../tasks/components/TaskForm'
import moment from "moment"

Vue.component('request-detail', RequestDetail);
Vue.component('avatar-image', AvatarImage);
Vue.component('task-screen', TaskForm);

Vue.prototype.moment = moment;
