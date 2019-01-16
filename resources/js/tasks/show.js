import Vue from 'vue'
import TaskForm from './components/TaskForm'
import TaskView from './components/TaskView'
import AvatarImage from '../components/AvatarImage'
import MonacoEditor from "vue-monaco";
import debounce from 'lodash/debounce';

Vue.component('task-screen', TaskForm);
Vue.component('task-view', TaskView);
Vue.component('avatar-image', AvatarImage);
Vue.component('monaco-editor', MonacoEditor);
window.debounce = debounce;
