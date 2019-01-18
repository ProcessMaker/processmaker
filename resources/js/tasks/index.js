import Vue from "vue";
import TasksList from "./components/TasksList";

new Vue({
    el: "#tasks",
    data: {
        filter: "",
        inOverdueMessage: ""
    },
    components: {TasksList},
    methods: {
        setInOverdueMessage(inOverdue) {
            let taskText  = (inOverdue > 1) ? "tasks" : "task";
            this.inOverdueMessage = "You have " + inOverdue + " overdue " + taskText + " pending";
        }
    }
});
