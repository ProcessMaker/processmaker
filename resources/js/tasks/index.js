import Vue from "vue";
import TasksList from "./components/TasksList";
import Multiselect from 'vue-multiselect'

new Vue({
    el: "#tasks",
    data: {
        filter: "",
        inOverdueMessage: "",
        advanced: false,
        isLoading: {
            process: false,
            requester: false,
            status: false,
            participants: false,
        },
        title: "All Request",
        value: null,
        pmql: "",
        task: "",
        request: "",
        assignee: "",
        taskOptions: [],
        requestOptions: [],
        assigneeOptions: [],
    },
    components: { TasksList, Multiselect },
    mounted() {
        this.getAll()
    },
    methods: {
        setInOverdueMessage(inOverdue) {
            let taskText = (inOverdue > 1) ? "tasks" : "task";
            this.inOverdueMessage = "You have " + inOverdue + " overdue " + taskText + " pending";
        },
        runSearch(advanced) {
            console.log("runSearch", advanced)
        },
        buildPmql() {
            console.log("buildPML")
        },
        getInitials(firstname, lastname) {
            console.log("intitals")
        },
        allLoading(value) {
            this.isLoading.process = value;
            this.isLoading.status = value;
            this.isLoading.requester = value;
            this.isLoading.participants = value;
        },
        getAll() {
            console.log("GETAL:L")
        },
        getTasks() {
            console.log('tasks')
        },
        getAssignee(query) {
            console.log("ASSIGNEE")
        },
        getRequests() {
            console.log("REQUESTS")
        }
    }
});
