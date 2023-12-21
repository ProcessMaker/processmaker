const PMColumnFilterPopoverRequestMixin = {
  data() {
    return {
      advancedFilter: [],
      viewParticipants: []
    };
  },
  methods: {
    onChangeSort(value) {
      this.orderDirection = value;
    },
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
    getFormat(column) {
      let format = "string";
      if (column.format) {
        format = column.format;
      }
      if (column.field === "status" || column.field === "participants") {
        format = "stringSelect";
      }
      return format;
    },
    getFormatRange(column) {
      let formatRange = [];
      if (column.field === "status") {
        formatRange = this.getStatus();
      }
      if (column.field === "participants") {
        formatRange = this.getParticipants();
      }
      return formatRange;
    },
    getOperators(column) {
      let operators = [];
      if (column.field === "status" || column.field === "participants") {
        operators = ["=", "in"];
      }
      return operators;
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
    getParticipants() {
      //todo: Users can be numerous; therefore, the control should be a search 
      //field with a suggestion list type.
      if (this.viewParticipants.length > 0) {
        return this.viewParticipants;
      }
      let page = 1;
      let perPage = 100;
      let filter = "";
      let orderBy = "username";
      let orderDirection = "asc";
      let url = "users" +
              "?page=" + page +
              "&per_page=" + perPage +
              "&filter=" + filter +
              "&order_by=" + orderBy +
              "&order_direction=" + orderDirection;
      ProcessMaker.apiClient.get(url).then(response => {
        for (let i in response.data.data) {
          this.viewParticipants.push(response.data.data[i].username);
        }
      });
      return this.viewParticipants;
    }
  }
};
export default PMColumnFilterPopoverRequestMixin;
