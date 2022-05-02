import Vue from "vue";
import TasksList from "./components/TasksList";
import AdvancedSearch from "../components/AdvancedSearch";

new Vue({
  el: "#tasks",
  components: { TasksList, AdvancedSearch },
  data: {
    filter: "",
    pmql: "",
    status: [],
    inOverdueMessage: "",
  },
  created() {
    const params = new URL(document.location).searchParams;
    const statusParam = params.get("status");
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
      name: status,
      value: status,
    });

    // translate status labels when available
    window.ProcessMaker.i18nPromise.then(() => {
      this.status.forEach((item) => {
        item.name = this.$t(item.name);
      });
    });
  },
  methods: {
    onChange(query) {
      this.pmql = query;
    },
    onSearch() {
      this.$refs.taskList.fetch(true);
    },
    setInOverdueMessage(inOverdue) {
      const taskText = (inOverdue > 1) ? this.$t("Tasks").toLowerCase() : this.$t("Task").toLowerCase();
      this.inOverdueMessage = this.$t("You have {{ inOverDue }} overdue {{ taskText }} pending", { inOverDue: inOverdue, taskText });
    },
  },
});
