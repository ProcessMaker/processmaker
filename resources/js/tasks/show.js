import Vue from 'vue';
import Vuex from 'vuex';
import Task from '@processmaker/screen-builder';
import TaskView from './components/TaskView';
import AvatarImage from '../components/AvatarImage';
import MonacoEditor from "vue-monaco";
import debounce from 'lodash/debounce';
import Timeline from '../components/Timeline';
import TimelineItem from '../components/TimelineItem';

Vue.use(Vuex);
Vue.use('task', Task);
Vue.component('task-view', TaskView);
Vue.component('avatar-image', AvatarImage);
Vue.component('monaco-editor', MonacoEditor);
Vue.component('timeline', Timeline);
Vue.component('timeline-item', TimelineItem);
window.debounce = debounce;
window.Vuex = Vuex;
