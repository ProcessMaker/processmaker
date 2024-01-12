const PMColumnFilterCommonMixin = {
  data() {
    return {
      advancedFilter: [],
      userId: window.Processmaker.userId,
      viewAssignee: [],
      viewParticipants: []
    };
  },
  methods: {
    getViewConfigFilter() {
      return [
        {
          "type": "string",
          "includes": ["=", "<", "<=", ">", ">=", "contains", "regex"],
          "control": "PMColumnFilterOpInput",
          "input": ""
        },
        {
          "type": "string",
          "includes": ["between"],
          "control": "PMColumnFilterOpBetween",
          "input": []
        },
        {
          "type": "string",
          "includes": ["in"],
          "control": "PMColumnFilterOpIn",
          "input": []
        },
        {
          "type": "datetime",
          "includes": ["=", "<", "<=", ">", ">=", "contains", "regex"],
          "control": "PMColumnFilterOpDatetime",
          "input": ""
        },
        {
          "type": "datetime",
          "includes": ["between"],
          "control": "PMColumnFilterOpBetweenDatepicker",
          "input": []
        },
        {
          "type": "datetime",
          "includes": ["in"],
          "control": "PMColumnFilterOpInDatepicker",
          "input": []
        },
        {
          "type": "stringSelect",
          "includes": ["="],
          "control": "PMColumnFilterOpSelect",
          "input": ""
        },
        {
          "type": "stringSelect",
          "includes": ["in"],
          "control": "PMColumnFilterOpSelectMultiple",
          "input": []
        },
        {
          "type": "boolean",
          "includes": ["="],
          "control": "PMColumnFilterOpBoolean",
          "input": false
        }
      ];
    },
    onApply(json, index) {
      for (let i in json) {
        json[i].subject.type = this.getTypeColumnFilter(json[i].subject.value);
        json[i].subject.value = this.getAliasColumnForFilter(json[i].subject.value);
      }
      this.advancedFilterInit(this.tableHeaders.length);
      this.advancedFilter[index] = json;
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch();
    },
    onClear(index) {
      this.advancedFilter[index] = [];
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch();
    },
    onChangeSort(value, field) {
      this.setOrderByProps(field, value);
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch();
    },
    onUpdate(object, index) {
      if (object.$refs.pmColumnFilterForm &&
              this.advancedFilter.length > 0 &&
              this.advancedFilter[index] &&
              this.advancedFilter[index].length > 0) {
        object.$refs.pmColumnFilterForm.setValues(this.advancedFilter[index]);
      }
    },
    onShown(object, index) {
      let cancel = object.$refs.pmColumnFilterForm.$el.getElementsByClassName("pm-filter-form-button-cancel");
      cancel[0].focus();
    },
    getAdvancedFilter() {
      let flat = this.advancedFilter.flat(1);
      return flat.length > 0 ? "&advanced_filter=" + JSON.stringify(flat) : "";
    },
    getUrlUsers(filter) {
      let page = 1;
      let perPage = 100;
      let orderBy = "username";
      let orderDirection = "asc";
      let url = "users" +
              "?page=" + page +
              "&per_page=" + perPage +
              "&filter=" + filter +
              "&order_by=" + orderBy +
              "&order_direction=" + orderDirection;
      return url;
    },
    getFormat(column) {
      let format = "string";
      if (column.format) {
        format = column.format;
      }
      if (column.field === "status" || column.field === "assignee" || column.field === "participants") {
        format = "stringSelect";
      }
      return format;
    },
    getFormatRange(column) {
      let formatRange = [];
      if (column.field === "status") {
        formatRange = this.getStatus();
      }
      if (column.field === "assignee") {
        formatRange = this.viewAssignee;
      }
      if (column.field === "participants") {
        formatRange = this.viewParticipants;
      }
      return formatRange;
    },
    getOperators(column) {
      let operators = [];
      if (column.field === "status" || column.field === "assignee" || column.field === "participants") {
        operators = ["=", "in"];
      }
      return operators;
    },
    getAssignee(filter) {
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewAssignee.push(response.data.data[i].username);
        }
      });
    },
    getParticipants(filter) {
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewParticipants.push(response.data.data[i].username);
        }
      });
    },
    advancedFilterInit(size) {
      for (let i = 0; i < size; i++) {
        if (!(i in this.advancedFilter)) {
          this.advancedFilter[i] = [];
        }
      }
    },
    markStyleWhenColumnSetAFilter() {
      for (let i in this.tableHeaders) {
        this.tableHeaders[i].filterApplied = false;
        this.tableHeaders[i].sortAsc = false;
        this.tableHeaders[i].sortDesc = false;
      }
      for (let i in this.tableHeaders) {
        if (this.tableHeaders[i].field === this.orderBy) {
          this.tableHeaders[i].filterApplied = true;
          let sort = this.sortOrder[0].direction;
          this.tableHeaders[i].sortAsc = (sort.toLowerCase() === "asc");
          this.tableHeaders[i].sortDesc = (sort.toLowerCase() === "desc");
        }
      }
      for (let i in this.advancedFilter) {
        if (i in this.tableHeaders && this.advancedFilter[i].length > 0) {
          this.tableHeaders[i].filterApplied = true;
        }
      }
    },
    getFilterConfiguration(name) {
      if ("filter_user" in window.Processmaker) {
        this.setFilterPropsFromConfig(window.Processmaker.filter_user);
        return;
      }
      let url = "users/get_filter_configuration/" + name;
      ProcessMaker.apiClient.get(url).then(response => {
        this.setFilterPropsFromConfig(response.data.data);
      });
    },
    setFilterPropsFromConfig(config) {
      if (config.filter && config.filter instanceof Array) {
        this.advancedFilter = config.filter;
      }
      if (config?.order?.by && config?.order?.direction) {
        this.setOrderByProps(config.order.by, config.order.direction);
      }
      this.markStyleWhenColumnSetAFilter();
    }
  }
};
export default PMColumnFilterCommonMixin;