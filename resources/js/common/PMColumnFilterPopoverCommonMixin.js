const PMColumnFilterCommonMixin = {
  data() {
    return {
      advancedFilter: [],
      userId: window.Processmaker.userId
    };
  },
  methods: {
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
    },
    getFilterConfiguration(user_id, filterName) {
      let url = "users/" + user_id + "/get_filter_configuration/" + filterName;
      ProcessMaker.apiClient.get(url).then(response => {
        this.advancedFilter = response.data.data;
        this.verifyIfHeaderContainFilter();
      });
    },
    storeFilterConfiguration(user_id, filterName) {
      let url = "users/" + user_id + "/store_filter_configuration/" + filterName;
      ProcessMaker.apiClient.put(url, this.advancedFilter);
    },
    advancedFilterInit(size) {
      for (let i = 0; i < size; i++) {
        if (!(i in this.advancedFilter)) {
          this.advancedFilter[i] = [];
        }
      }
    },
    verifyIfHeaderContainFilter() {
      for (let i in this.advancedFilter) {
        if (i in this.tableHeaders && this.advancedFilter[i].length > 0) {
          this.tableHeaders[i].filterApplied = true;
        }
      }
    }
  }
};
export default PMColumnFilterCommonMixin;