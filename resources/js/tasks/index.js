import Vue from "vue";
import TasksList from "./components/TasksList";
import setDefaultAdvancedFilterStatus from "../common/setDefaultAdvancedFilterStatus";

Vue.component("TasksList", TasksList);

new Vue({
  el: "#tasks",
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
    isDataLoading: false,
    inbox: true,
    priority: false,
    draft: false,
    priorityFilter: [
      {
        "subject": {
          "type": "Field",
          "value": "is_priority"
        },
        "operator": "=",
        "value": true,
        "_column_field": "is_priority",
        "_column_label": "Priority",
        "_hide_badge": true
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
        "_column_label": "Draft",
        "_hide_badge": true
      }
    ],
  },
  mounted() {
    ProcessMaker.EventBus.$on('advanced-search-addition', (component) => {
      this.additions.push(component);
    });
    this.onInbox();
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
    setDefaultAdvancedFilterStatus(status);

    if (this.urlPmql && this.urlPmql !== "") {
      this.onSearch();
    }
  },
  methods: {
    switchTab(tab) {
      this.inbox = tab === "inbox";
      this.draft = tab === "draft";
      this.priority = tab === "priority";
      switch (tab) {
        case "inbox":
          this.onInbox();
          break;
        case "priority":
          this.onSwitchTab("is_priority", this.priorityFilter);
          break;
        case "draft":
          this.onSwitchTab("draft", this.draftFilter);
          break;
        default:
          break;
      }
    },
    dataLoading(value) {
      this.isDataLoading = value;
    },
    onInbox() {
      this.removeTabFilter(this.priorityField);
      this.removeTabFilter(this.draftField);
      this.fetchTasks();
    },

    onSwitchTab(field, filter) {
      this.removeTabFilter(this.priorityField);
      this.removeTabFilter(this.draftField);
      const taskListComponent = this.$refs.taskList;
      taskListComponent.advancedFilter[field] = filter;
      taskListComponent.markStyleWhenColumnSetAFilter();
      taskListComponent.storeFilterConfiguration();
      this.fetchTasks();
    },
    removeTabFilter(tab) {
      const taskListComponent = this.$refs.taskList;
      taskListComponent.advancedFilter[tab] = [];
      taskListComponent.markStyleWhenColumnSetAFilter();
      taskListComponent.storeFilterConfiguration();
    },
    fetchTasks() {
      const taskListComponent = this.$refs.taskList;
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
    onInboxRules() {
      window.location.href = "/tasks/rules";
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
