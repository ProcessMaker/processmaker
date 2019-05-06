import Vue from "vue";
import TasksList from "./components/TasksList";
import Multiselect from 'vue-multiselect'

new Vue({
    el: "#tasks",
    data: {
        filter: "",
        inOverdueMessage: "",
        advanced: false,
        title: "All Request",
        value: null,
        pmql: "",
        request: [],
        name: [],
        status: [],
        requestOptions: [],
        nameOptions: [],
        statusOptions: [],
        isLoading: {
          request: false,
          name: false,
          status: false,
        }
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
        toggleAdvanced() {
            if (this.advanced) {
                this.advanced = false;
            } else {
                this.advanced = true;
                Vue.nextTick().then(() => {
                    this.$refs.search_input.focus();
                });
            }
        },
        runSearch(advanced) {
          if (! advanced) {
            this.buildPmql();
          }
          this.$refs.taskList.fetch(true);
        },
        buildPmql() {          
          let clauses = [];
          
          //Parse request
          if (this.request.length) {
            let string = '';
            this.request.forEach((request, key) => {
              string += 'request = "' + request.name + '"';
              if (key < this.request.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }
                    
          //Parse names
          if (this.name.length) {
            let string = '';
            this.name.forEach((name, key) => {
              string += 'task = "' + name.name + '"';
              if (key < this.name.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }

          //Parse status
          if (this.status.length) {
            let string = '';
            this.status.forEach((status, key) => {
              string += 'status = "' + status.value + '"';
              if (key < this.status.length - 1) string += ' OR ';
            });
            clauses.push(string);
          }
          
          this.pmql = '';
          clauses.forEach((string, key) => {
            this.pmql += '(';
            this.pmql += string;
            this.pmql += ')';
            if (key < clauses.length - 1) this.pmql += ' AND ';
          });          
        },
        getInitials(firstname, lastname) {
            if (firstname) {
              return firstname.match(/./u)[0] + lastname.match(/./u)[0]
            } else {
              return null;
            }
        },
        allLoading(value) {
          this.isLoading.request = value;
          this.isLoading.status = value;
          this.isLoading.name = value;
        },
        getAll(){
          this.allLoading(true);
          ProcessMaker.apiClient
              .get("/tasks/search?type=task_all", { baseURL: '' })
              .then(response => {
                  this.requestOptions = response.data.request;
                  this.statusOptions = response.data.status;
                  this.nameOptions = response.data.name;
                  this.allLoading(false);
                  setTimeout(3000)
              });
        },
        getStatus() {
          this.isLoading.status = true;
          ProcessMaker.apiClient
              .get("/tasks/search?type=task_status", { baseURL: '' })
              .then(response => {
                  this.statusOptions = response.data;
                  this.isLoading.status = false
                  setTimeout(3000)
              });
        },
        getRequests(query) {
            this.isLoading.request = true
            ProcessMaker.apiClient
                .get("/tasks/search?type=request&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.requestOptions = response.data;
                    this.isLoading.request = false
                    setTimeout(3000)
                });
        },
        getNames(query) {
            this.isLoading.name = true
            ProcessMaker.apiClient
                .get("/tasks/search?type=name&filter=" + query, { baseURL: '' })
                .then(response => {
                    this.nameOptions = response.data;
                    this.isLoading.name = false
                    setTimeout(3000)
                });
        }
    }
});
