import Vue from "vue";
import RequestDetail from "./components/RequestDetail";
import AvatarImage from "../components/AvatarImage";
import TaskForm from '../tasks/components/TaskForm';
import RequestErrors from './components/RequestErrors';
import MonacoEditor from "vue-monaco";
import debounce from 'lodash/debounce';
import Comments from '../components/Comments'
import RequestScreens from '../requests/components/RequestScreens';

Vue.component('request-detail', RequestDetail);
Vue.component('avatar-image', AvatarImage);
Vue.component('task-screen', TaskForm);
Vue.component('request-errors', RequestErrors);
Vue.component('monaco-editor', MonacoEditor);
Vue.component('comments', Comments);
Vue.component('request-screens', RequestScreens);
window.debounce = debounce;
