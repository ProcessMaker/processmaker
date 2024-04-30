import { get, cloneDeep } from "lodash";

const PMColumnFilterCommonMixin = {
  props: {
    advancedFilterProp: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      advancedFilter: {},
      userId: window.Processmaker.userId,
      viewAssignee: [],
      viewParticipants: [],
    };
  },
  watch: {
    advancedFilterProp: {
      deep: true,
      handler() {
        this.getFilterConfiguration();
        this.fetch();
      }
    }
  },
  methods: {
    storeFilterConfiguration() {
      const {order, type} = this.filterConfiguration();

      // If advanced filter was provided as a prop, do not save the filter
      // or overwrite the global advanced_filter, instead emit the filter.
      if (this.advancedFilterProp !== null) {
        this.$emit("advanced-filter-updated", {
          filters: this.formattedFilter(),
          order
        });
        return;
      }

      let url = "users/store_filter_configuration/";
      if (this.$props.columns && this.savedSearch) {
        url += "savedSearch|" + this.savedSearch;
      } else {
        url += type;
        if (Processmaker.status) {
          url += "|" + Processmaker.status;
        }
      }
      let config = {
        filters: this.formattedFilter(),
        order
      };

      ProcessMaker.apiClient.put(url, config);
      window.ProcessMaker.advanced_filter = config;
      window.ProcessMaker.EventBus.$emit("advanced-filter-updated");
    },
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
          "includes": ["<", "<=", ">", ">="],
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
    addAliases(json, key, label) {
      let type, value;
      for (let i in json) {
        type = this.getTypeColumnFilter(key, json[i].subject.type);
        value = this.getAliasColumnForFilter(key, json[i].subject.value);
        json[i].subject.type = type;
        json[i].subject.value = value;
        json[i]._column_field = key;
        json[i]._column_label = label;

        if (json[i].or && json[i].or.length > 0) {
          this.addAliases(json[i].or, key, label);
        }
      }
    },
    getTypeColumnFilter(field, defaultType = 'Field') {
      return this.tableHeaders.find(column => column.field === field)?.filter_subject?.type || defaultType;
    },
    getAliasColumnForFilter(field, defaultValue) {
      return this.tableHeaders.find(column => column.field === field)?.filter_subject?.value || defaultValue;
    },
    getAliasColumnForOrderBy(value) {
      return this.tableHeaders.find(column => column.field === value)?.order_column || value;
    },
    onApply(json, index) {
      this.advancedFilterInit();
      this.advancedFilter[index] = json;
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch(true);
    },
    onClear(index) {
      this.advancedFilter[index] = [];
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch(true);
    },
    onChangeSort(value, field) {
      this.setOrderByProps(field, value);
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch(true);
    },
    onUpdate(object, index) {
      if (object.$refs.pmColumnFilterForm &&
              index in this.advancedFilter &&
              this.advancedFilter[index].length > 0) {
        object.$refs.pmColumnFilterForm.setValues(this.advancedFilter[index]);
      }
    },
    formattedFilter() {
      const filterCopy = cloneDeep(this.advancedFilter);
      Object.keys(filterCopy).forEach((key) => {
        if (filterCopy[key].length === 0) {
          delete filterCopy[key];
        }
        const label = this.tableHeaders.find(column => column.field === key)?.label;
        this.addAliases(filterCopy[key], key, label);
      });
      return Object.values(filterCopy).flat(1);
    },
    getAdvancedFilter() {
      let formattedFilter = this.formattedFilter().map(obj =>
        // Remove keys that start with _
        Object.fromEntries(Object.entries(obj).filter(([key, _]) => !key.startsWith('_')))
      );
      return formattedFilter.length > 0 ? "&advanced_filter=" + encodeURIComponent(JSON.stringify(formattedFilter)) : "";
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
        if (format === "int") {
          // We don't have a field for integers
          format = "string";
        }
      }
      if (column.field === "status") {
        format = "stringSelect";
      }
      return format;
    },
    getFormatRange(column) {
      let formatRange = [];
      if (column.field === "status") {
        formatRange = this.getStatus();
      }
      return formatRange;
    },
    getOperators(column) {
      let operators = [];
      if (column.field === "case_title" || column.field === "name" || column.field === "process" || column.field === "task_name" || column.field === "participants" || column.field === "assignee") {
        operators = ["=", "in", "contains", "regex"];
      }
      if (column.field === "status") {
        operators = ["=", "in"];
      }
      if (column.field === "initiated_at" || column.field === "completed_at" || column.field === "due_at") {
        operators = ["<", "<=", ">", ">=", "between"];
      }
      return operators;
    },
    getAssignee(filter) {
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewAssignee.push({
            text: response.data.data[i].username,
            value: response.data.data[i].id
          });
        }
      });
    },
    getParticipants(filter) {
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewParticipants.push({
            text: response.data.data[i].username,
            value: response.data.data[i].id
          });
        }
      });
    },
    advancedFilterInit() {
      for (let i in this.tableHeaders) {
        if (!(this.tableHeaders[i].field in this.advancedFilter)) {
          this.advancedFilter[this.tableHeaders[i].field] = [];
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
          let sort = this.sortOrder[0].direction;
          this.tableHeaders[i].sortAsc = (sort.toLowerCase() === "asc");
          this.tableHeaders[i].sortDesc = (sort.toLowerCase() === "desc");
        }
      }
      for (let i in this.tableHeaders) {
        if (this.tableHeaders[i].field in this.advancedFilter &&
                this.advancedFilter[this.tableHeaders[i].field].length > 0) {
          this.tableHeaders[i].filterApplied = true;
        }
      }
    },
    getFilterConfiguration() {
      const filters = {};
      let inputAdvancedFilter;
      let order = null;

      if (this.advancedFilterProp !== null) {
        inputAdvancedFilter = this.advancedFilterProp.filters;
        order = this.advancedFilterProp.order;
      } else {
        inputAdvancedFilter = get(window, 'ProcessMaker.advanced_filter.filters', []);
        order = get(window, 'ProcessMaker.advanced_filter.order');
      }

      inputAdvancedFilter.forEach((filter) => {
        const key = filter._column_field || 'N/A';
        if (!(key in filters)) {
          filters[key] = [];
        }
        filters[key].push(filter);
      });
      this.advancedFilter = filters;

      if (order?.by && order?.direction) {
        this.setOrderByProps(order.by, order.direction);
      }

      this.$nextTick(() => {
        this.markStyleWhenColumnSetAFilter();
      });

      if (this.advancedFilterProp === null) {
        window.ProcessMaker.EventBus.$emit("advanced-filter-updated");
      }
    },
    //to do: this should be used in the future if refreshing the table elements is required.
    refreshData() {
      this.getFilterConfiguration();
      this.markStyleWhenColumnSetAFilter();
      this.storeFilterConfiguration();
      this.fetch(true);
    }
  }
};
export default PMColumnFilterCommonMixin;
