import { get, cloneDeep } from "lodash";
import { isTaskStatusColumnFilter } from "./setDefaultAdvancedFilterStatus";

const PMColumnFilterCommonMixin = {
  props: {
    autosaveFilter: {
      type: Boolean,
      default: true
    },
    advancedFilterProp: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      advancedFilter: {},
      userId: window.Processmaker.userId
    };
  },
  watch: {
    advancedFilterProp: {
      deep: true,
      handler(current, old) {
        if (_.isEqual(current, old)) {
          return;
        }
        this.getFilterConfiguration();
        this.fetch();
      }
    }
  },
  mounted() {
    this.$root.$on("load-with-filter", (filter) => {
      _.set(window, "ProcessMaker.advanced_filter.filters", filter);
      this.getFilterConfiguration();
      this.fetch();
    });
  },
  methods: {
    /**
     * Task inbox: if no Status column filter is present, default to the same behavior as
     * tasks/index.js (In Progress, or Completed / Self Service from URL query).
     */
    applyTaskInboxDefaultStatusIfMissing() {
      if (typeof this.filterConfiguration !== "function") {
        return;
      }
      if (this.filterConfiguration().type !== "taskFilter") {
        return;
      }
      if (this.advancedFilterProp !== null) {
        return;
      }
      const savedSearch = this.$props.savedSearch;
      if (savedSearch !== false && savedSearch != null && savedSearch !== "") {
        return;
      }
      this.advancedFilterInit();
      const statusFilters = this.advancedFilter.status;
      if (Array.isArray(statusFilters) && statusFilters.length > 0) {
        return;
      }

      let value = "In Progress";
      try {
        const params = new URL(document.location).searchParams;
        if (params.get("status") === "CLOSED") {
          value = "Completed";
        } else if (params.get("status") === "SELF_SERVICE") {
          value = "Self Service";
        }
      } catch (e) {
        // no window (tests)
      }

      this.advancedFilter.status = [
        {
          subject: { type: "Field", value: "status" },
          operator: "=",
          value,
          _column_field: "status",
          _column_label: "Status",
        },
      ];
    },
    storeFilterConfiguration() {
      // If advanced filter was provided as a prop, do not save the filter
      // or overwrite the global advanced_filter, instead emit the filter.
      if (this.advancedFilterProp !== null) {
        const { order } = this.filterConfiguration();
        this.$emit("advanced-filter-updated", {
          filters: this.formattedFilter(),
          order
        });
        return;
      }

      this.applyTaskInboxDefaultStatusIfMissing();
      const { order, type } = this.filterConfiguration();

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
      if (this.autosaveFilter) {
        ProcessMaker.apiClient.put(url, config);
      }
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
    /**
     * Group advanced_filter rows by column. Some saved payloads omit _column_field or use "N/A",
     * so status (or process version) rows were split across buckets and never merged into `in`.
     */
    resolveAdvancedFilterGroupKey(filter) {
      let key = filter._column_field;
      if (!key || key === "N/A") {
        if (isTaskStatusColumnFilter(filter)) {
          return "status";
        }
        if (filter.subject?.value === "process_version_alternative") {
          return "process_version_alternative";
        }
        return "N/A";
      }
      return key;
    },
    /**
     * Columns that use stringSelect with "=" rows; multiple sibling "=" filters are AND-ed
     * by the API and can never match (e.g. status In Progress AND Completed). Collapse those
     * into one filter object using nested `or` so each value stays operator "=" (OR semantics
     * in SQL — same as IN). Leaves existing OR-nested structures unchanged.
     *
     * @param {Array} filters - Filters for one column from PMColumnFilterForm
     * @param {string} columnField - Column field name (e.g. status)
     * @returns {Array}
     */
    mergeFlatEnumEqualsToOrChainForColumn(filters, columnField) {
      if (!['status', 'process_version_alternative'].includes(columnField)) {
        return filters;
      }
      if (!Array.isArray(filters) || filters.length < 2) {
        return filters;
      }
      const hasNestedOr = filters.some((f) => Array.isArray(f.or) && f.or.length > 0);
      if (hasNestedOr) {
        return filters;
      }
      const allEquals = filters.every((f) => f.operator === '=');
      if (!allEquals) {
        return filters;
      }
      const seen = new Set();
      const uniqueRows = [];
      for (const f of filters) {
        if (seen.has(f.value)) {
          continue;
        }
        seen.add(f.value);
        uniqueRows.push(f);
      }
      if (uniqueRows.length < 2) {
        return uniqueRows;
      }
      const subjectSource =
        filters.find((f) => f.subject?.value === columnField)
        || filters.find((f) => f.subject?.type === "Field" && f.subject?.value)
        || filters.find((f) => isTaskStatusColumnFilter(f));
      const defaultSubject = subjectSource
        ? { ...subjectSource.subject }
        : { type: "Field", value: columnField };
      const subjectFor = (row) =>
        row.subject && (row.subject.value !== undefined || row.subject.type === "Status")
          ? { ...row.subject }
          : defaultSubject;
      const equalsLeaf = (row) => ({
        subject: subjectFor(row),
        operator: "=",
        value: row.value,
      });
      let node = equalsLeaf(uniqueRows[uniqueRows.length - 1]);
      for (let i = uniqueRows.length - 2; i >= 1; i--) {
        const row = uniqueRows[i];
        node = {
          subject: subjectFor(row),
          operator: "=",
          value: row.value,
          or: [node],
        };
      }
      const first = uniqueRows[0];
      return [
        {
          subject: subjectFor(first),
          operator: "=",
          value: first.value,
          or: [node],
        },
      ];
    },
    onApply(json, index) {
      this.advancedFilterInit();
      this.advancedFilter[index] = this.mergeFlatEnumEqualsToOrChainForColumn(json, index);
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
          return;
        }
        filterCopy[key] = this.mergeFlatEnumEqualsToOrChainForColumn(filterCopy[key], key);
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
    getFormat(column) {
      let format = "string";
      if (column.format) {
        format = column.format;
        if (format === "int") {
          // We don't have a field for integers
          format = "string";
        }
      }

      if (['status', 'process_version_alternative'].includes(column.field)) {
        format = "stringSelect";
      }

      return format;
    },
    /**
     * Returns the available alternatives for process version filtering
     * Used by getFormatRange() to populate dropdown options
     * 
     * @returns {Array} Array of objects with value and text properties
     */
    getAlternatives() {
      return [
        { value: 'A', text: 'A' },
        { value: 'B', text: 'B' },
      ];
    },
    getFormatRange(column) {
      let formatRange;

      switch (column.field) {
        case 'status':
          formatRange = this.getStatus();
          break;
        case 'process_version_alternative':
          formatRange = this.getAlternatives();
          break;
        default:
          formatRange = [];
          break;
      }

      return formatRange;
    },
    getOperators(column) {
      let operators = [];
      if (column.field === "case_title" || column.field === "name" || column.field === "process" || column.field === "task_name" || column.field === "element_name" || column.field === "participants" || column.field === "assignee") {
        operators = ["=", "in", "contains", "regex"];
      }

      if (['status', 'process_version_alternative'].includes(column.field)) {
        operators = ["=", "in"];
      }

      if (column.field === "initiated_at" || column.field === "completed_at" || column.field === "due_at") {
        operators = ["<", "<=", ">", ">=", "between"];
      }

      return operators;
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
        if (this.tableHeaders[i].order_column !== undefined) {
          if (this.orderBy === this.tableHeaders[i].order_column) {
            let sort = this.sortOrder[0].direction;
            this.tableHeaders[i].sortAsc = (sort.toLowerCase() === "asc");
            this.tableHeaders[i].sortDesc = (sort.toLowerCase() === "desc");
          }
        } else if (this.orderBy.endsWith(this.tableHeaders[i].field)) {
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
        const key = this.resolveAdvancedFilterGroupKey(filter);
        if (!(key in filters)) {
          filters[key] = [];
        }
        filters[key].push(filter);
      });
      Object.keys(filters).forEach((key) => {
        filters[key] = this.mergeFlatEnumEqualsToOrChainForColumn(filters[key], key);
      });
      this.advancedFilter = filters;

      this.applyTaskInboxDefaultStatusIfMissing();

      if (
        this.advancedFilterProp === null &&
        typeof this.filterConfiguration === "function" &&
        this.filterConfiguration().type === "taskFilter"
      ) {
        window.ProcessMaker.advanced_filter = {
          ...get(window, "ProcessMaker.advanced_filter", {}),
          filters: this.formattedFilter(),
          order: order || get(window, "ProcessMaker.advanced_filter.order"),
        };
      }

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
