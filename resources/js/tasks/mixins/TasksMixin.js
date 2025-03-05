export default {
  data() {
    return {
      showOldTaskScreen: false,
      urlConfiguration: "users/configuration",
      showMenu: false,
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
      tab: "inbox",
      inboxCount: null,
      draftCount: null,
      priorityCount: null,
      priorityFilter: [
        {
          subject: {
            type: "Field",
            value: "is_priority",
          },
          operator: "=",
          value: true,
          _column_field: "is_priority",
          _column_label: "Priority",
          _hide_badge: true,
        },
      ],
      draftFilter: [
        {
          subject: {
            type: "Relationship",
            value: "draft.id",
          },
          operator: ">",
          value: 0,
          _column_field: "draft",
          _column_label: "Draft",
          _hide_badge: true,
        },
      ],
    };
  },
  computed: {
    effectiveSavedsearchDefaultsEditRoute() {
      return (
        this.savedsearchDefaultsEditRoute ||
        window.ProcessMaker.savedsearchDefaultsEditRoute
      );
    },
  },
  methods: {
    defineUserConfiguration() {
      this.localUserConfiguration = JSON.parse(
        window.ProcessMaker.userConfiguration || "{}"
      );
      if (this.localUserConfiguration.tasks_inbox) {
        this.showMenu = this.localUserConfiguration.tasks_inbox.isMenuCollapse;
      } else {
        this.showMenu = false;
        this.localUserConfiguration.tasks_inbox = {
          isMenuCollapse: false,
        };
      }
    },
    hideMenu() {
      this.showMenu = !this.showMenu;
      this.updateUserConfiguration();
    },
    updateUserConfiguration() {
      this.localUserConfiguration.tasks_inbox.isMenuCollapse = this.showMenu;
      ProcessMaker.apiClient
        .put(this.urlConfiguration, {
          ui_configuration: this.localUserConfiguration,
        })
        .catch((error) => {
          console.error("Error", error);
        });
    },
    switchTab(tab) {
      this.tab = tab;
      const taskListComponent = this.$refs.taskList;
      taskListComponent.advancedFilter[this.priorityField] = [];
      taskListComponent.advancedFilter[this.draftField] = [];
      switch (tab) {
        case "priority":
          taskListComponent.advancedFilter["is_priority"] = this.priorityFilter;
          break;
        case "draft":
          taskListComponent.advancedFilter["draft"] = this.draftFilter;
          break;
      }
      taskListComponent.markStyleWhenColumnSetAFilter();
      taskListComponent.storeFilterConfiguration();
      taskListComponent.fetch(true);
    },
    dataLoading(value) {
      this.isDataLoading = value;
    },
    onFetchTask() {
      this.inbox = true;
      this.priority = this.draft = false;
      let filters = window.ProcessMaker.advanced_filter?.filters;
      if (!Array.isArray(filters)) {
        filters = [];
      }
      filters.forEach((item) => {
        if (item._column_field === "is_priority") {
          this.priority = true;
          this.inbox = this.draft = false;
        }
        if (item._column_field === "draft") {
          this.draft = true;
          this.inbox = this.priority = false;
        }
      });
    },
    handleTabCount(value) {
      if (this.tab === "inbox") {
        this.inboxCount = value;
      }
      if (this.tab === "draft") {
        this.draftCount = value;
      }
      if (this.tab === "priority") {
        this.priorityCount = value;
      }
    },
    onFiltersPmqlChange(value) {
      this.filtersPmql = value[0];
      this.fullPmql = this.getFullPmql();
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
      let inOverdueMessage = "";
      if (inOverdue) {
        const taskText =
          inOverdue > 1
            ? this.$t("Tasks").toLowerCase()
            : this.$t("Task").toLowerCase();
        inOverdueMessage = this.$t(
          "You have {{ inOverDue }} overdue {{ taskText }} pending",
          { inOverDue: inOverdue, taskText }
        );
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
};
