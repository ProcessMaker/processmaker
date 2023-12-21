const PMColumnFilterCommonMixin = {
  data() {
    return {
      advancedFilter: []
    };
  },
  methods: {
    onApply(json, index) {
      this.advancedFilter[index] = json;
      this.tableHeaders[index].filterApplied = true;
      this.fetch();
    },
    onClear(index) {
      this.advancedFilter[index] = [];
      this.tableHeaders[index].filterApplied = false;
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
    getAdvancedFilter() {
      let flat = this.advancedFilter.flat(1);
      return flat.length > 0 ? "&advanced_filter=" + JSON.stringify(flat) : "";
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
    getStatus() {
      //todo: add labels "In Progress", "Completed", "Error", "Canceled"
      return ["ACTIVE", "FAILING", "COMPLETED", "CLOSED", "EVENT_CATCH", "TRIGGERED", "INCOMING"];
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
    }
  }
};
export default PMColumnFilterCommonMixin;