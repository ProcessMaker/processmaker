import Vue from "vue";
import TasksList from "./components/TasksList";
import { cloneDeep } from "lodash";

new Vue({
  el: "#tasks",
  components: { TasksList },
  data: {
    columns: window.Processmaker.defaultColumns || null,
    filter: "",
    pmql: "",
    urlPmql: "",
    filtersPmql: "",
    fullPmql: "",
    status: [],
    inOverdueMessage: "",
    additions: [],
    priorityField: "is_priority",
    draftField: "draft",
    priorityFilter: [
      {
        "subject": {
          "type": "Field",
          "value": "is_priority"
        },
        "operator": "=",
        "value": true,
        "_column_field": "is_priority",
        "_column_label": "Priority"
      }
    ],
    draftFilter: [
      {
        "subject": {
          "type": "Relationship",
          "value": "draft.id"
        },
        "operator": ">",
        "value": 0,
        "_column_field": "draft",
        "_column_label": "Draft"
      }
    ],
  },
  mounted() {
    const taskListComponent = this.$refs.taskList;
    taskListComponent.advancedFilter = {};
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
    switchTab(tab) {
      if (tab === "inbox") {
        this.onInbox();
      }
      if (tab === "priority") {
        this.removeTabFilter(this.draftField);
        this.onSwitchTab(this.priorityField);
      }
      if (tab === "draft") {
        this.removeTabFilter(this.priorityField);
        this.onSwitchTab(this.draftField);
      }
    },    
    onInbox() {
      const taskListComponent = this.$refs.taskList;
      this.removeTabFilter(this.priorityField);
      this.removeTabFilter(this.draftField);
      taskListComponent.fetch(true);
    },
    onSwitchTab(field) {
      let filter;
      if (field === "is_priority") {
        filter = this.priorityFilter;
      }
      if (field === "draft") {
        filter = this.draftFilter;
      }
      const taskListComponent = this.$refs.taskList;
      taskListComponent.advancedFilter[field] = filter;
      taskListComponent.markStyleWhenColumnSetAFilter();
      taskListComponent.storeFilterConfiguration();
      taskListComponent.fetch(true);
    },
    removeTabFilter(tab) {
      const taskListComponent = this.$refs.taskList;
      taskListComponent.advancedFilter[tab] = [];
      taskListComponent.markStyleWhenColumnSetAFilter();
      taskListComponent.storeFilterConfiguration();
      taskListComponent.fetch(true);
    },
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
