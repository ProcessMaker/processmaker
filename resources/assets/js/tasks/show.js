import Vue from 'vue'
import TaskForm from './components/TaskForm'

new Vue({
    el: '#task',
    data: {
    },
    components: {
        TaskForm
    },
    mounted() {
        // Listen for notifications
        let userId = document.head.querySelector('meta[name="user-id"]').content;
        Echo.private(`ProcessMaker.Model.User.${userId}`)
            .notification((token) => {
                ProcessMaker.pushNotification(token);
            });
    },
});
