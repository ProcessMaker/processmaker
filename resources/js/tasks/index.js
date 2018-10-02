import Vue from 'vue'
import TasksList from './components/TasksList'

new Vue({
    el: '#tasks',
    data: {
        filter: ''
    },
    components: {TasksList}
});
