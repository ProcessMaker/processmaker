import Vue from "vue";
import TasksList from "./components/TasksList";
import Multiselect from 'vue-multiselect'
import AdvancedSearch from "../components/AdvancedSearch";

new Vue({
  el: "#tasks",
  data: {
    filter: "",
    pmql: "",
    status: [],
    inOverdueMessage: "",
  },
  components: { TasksList, Multiselect, AdvancedSearch },
  created() {
    let params = new URL(document.location).searchParams;
    let statusParam = params.get("status");
    let status = "";

    switch (statusParam) {
      case "CLOSED":
        status = "Completed";
        break;
      case "SELF_SERVICE":
        status = "Self Service";
        break;
      default:
        status = "In Progress";
        break;
    }
    
    this.status.push({
      name: this.$t(status),
      value: status
    });
  },
  methods: {
    onChange: function(query) {
      this.pmql = query;
    },
    onSearch: function() {
      this.$refs.taskList.fetch(true);
    },
    setInOverdueMessage(inOverdue) {
      let taskText = (inOverdue > 1) ? "tasks" : "task";
      this.inOverdueMessage = this.$t("You have ") + inOverdue + this.$t(" overdue ") + taskText + this.$t(" pending");
    }
  }
});
