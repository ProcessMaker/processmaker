import Vue from 'vue'
import TaskForm from './components/TaskForm'
import TaskView from './components/TaskView'

new Vue({
    el: '#task',
    data: {
    },
    components: {
        TaskForm,
        TaskView
    },
    mounted() {
        // Listen for notifications
        let userId = document.head.querySelector('meta[name="user-id"]').content;
        Echo.private(`ProcessMaker.Model.User.${userId}`)
            .notification((token) => {
                ProcessMaker.pushNotification(token);
            });
    }
});
