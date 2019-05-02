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
            task: false,
            request: false,
            assignee: false,
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
            this.isLoading.task = value;
            this.isLoading.request = value;
            this.isLoading.assignee = value;
        },
        getAll() {
            console.log("GETALL")
        },
        getTasks(query) {
            this.isLoading.task = true
            ProcessMaker.apiClient
                .get("/tasks/search?type=tasks&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.taskOptions = response.data;
                    this.isLoading.task = false
                    setTimeout(3000)
                });
        },
        getRequests(query) {
            this.isLoading.requests = true
            ProcessMaker.apiClient
                .get("/tasks/search?type=requests&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.requestsOptions = response.data;
                    this.isLoading.requests = false
                    setTimeout(3000)
                });
        },
        getAssignee(query) {
            this.isLoading.assignee = true
            ProcessMaker.apiClient
                .get("/tasks/search?type=assignee&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.assigneeOptions = response.data;
                    this.isLoading.assignee = false
                    setTimeout(3000)
                });
        },

    }
});
