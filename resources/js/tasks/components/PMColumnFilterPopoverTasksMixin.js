import PMColumnFilterPopoverCommonMixin from "../../common/PMColumnFilterPopoverCommonMixin.js";

const PMColumnFilterPopoverTasksMixin = {
  mixins: [PMColumnFilterPopoverCommonMixin],
  data() {
    return {
      viewAssignee: []
    };
  },
  methods: {
    onApply(json, index) {
      this.advancedFilterInit(this.tableHeaders.length);
      this.advancedFilter[index] = json;
      this.tableHeaders[index].filterApplied = true;
      this.storeFilterConfiguration(this.userId, "task");
      this.fetch();
    },
    onClear(index) {
      this.advancedFilter[index] = [];
      this.tableHeaders[index].filterApplied = false;
      this.storeFilterConfiguration(this.userId, "task");
      this.fetch();
    },
    onChangeSort(value) {
      this.order_direction = value;
    },
    getFormat(column) {
      let format = "string";
      if (column.format) {
        format = column.format;
      }
      if (column.field === "status" || column.field === "assignee") {
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
      return formatRange;
    },
    getOperators(column) {
      let operators = [];
      if (column.field === "status" || column.field === "assignee") {
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
    }
  }
};
export default PMColumnFilterPopoverTasksMixin;