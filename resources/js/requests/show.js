import Vue from "vue";
import RequestDetail from "./components/RequestDetail";
import AvatarImage from "../components/AvatarImage";
import TaskForm from '../tasks/components/TaskForm';
import RequestErrors from './components/RequestErrors';

Vue.component('request-detail', RequestDetail);
Vue.component('avatar-image', AvatarImage);
Vue.component('task-screen', TaskForm);
Vue.component('request-errors', RequestErrors);
