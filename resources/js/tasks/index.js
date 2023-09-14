import Vue from "vue";
import TasksList from "./components/TasksList";

new Vue({
  el: "#tasks",
  components: { TasksList },
  data: {
    filter: "",
    pmql: "",
    urlPmql: "",
    filtersPmql: "",
    fullPmql: "",
    status: [],
    inOverdueMessage: "",
    additions: [],
  },
  mounted() {
    ProcessMaker.EventBus.$on('advanced-search-addition', (component) => {
      this.additions.push(component);
    });
  },
  created() {
    const params = new URL(document.location).searchParams;
    const statusParam = params.get("status");
    this.urlPmql = params.get('pmql');

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

    if (this.urlPmql && this.urlPmql !== "") {
      this.onSearch();
    }
  },
  methods: {
    onFiltersPmqlChange(value) {
      this.filtersPmql = value[0];
      this.fullPmql = this.getFullPmql();
      this.onSearch();
    },
    onNLQConversion(query) {
      this.onChange(query);
      this.onSearch();
    },
    onChange(query) {
      this.pmql = query;
      this.fullPmql = this.getFullPmql();
    },
    onSearch() {
      if (this.$refs.taskList) {
        this.$refs.taskList.fetch(true);
      }
    },
    setInOverdueMessage(inOverdue) {
      let inOverdueMessage = '';
      if (inOverdue) {
        const taskText = (inOverdue > 1) ? this.$t("Tasks").toLowerCase() : this.$t("Task").toLowerCase();
        inOverdueMessage = this.$t("You have {{ inOverDue }} overdue {{ taskText }} pending", { inOverDue: inOverdue, taskText });
      }
      this.inOverdueMessage = inOverdueMessage;
    },
    getFullPmql() {
      let fullPmqlString = "";

      if (this.filtersPmql && this.filtersPmql !== "") {
        fullPmqlString = this.filtersPmql;
      }

      if (fullPmqlString !== "" && this.pmql && this.pmql !== "") {
        fullPmqlString = `${fullPmqlString} AND ${this.pmql}`;
      }

      if (fullPmqlString === "" && this.pmql && this.pmql !== "") {
        fullPmqlString = this.pmql;
      }

      return fullPmqlString;
    },
  },
});
