import PMColumnFilterPopoverCommonMixin from "../../common/PMColumnFilterPopoverCommonMixin.js";

const PMColumnFilterPopoverRequestMixin = {
  mixins: [PMColumnFilterPopoverCommonMixin],
  data() {
    return {
      viewParticipants: []
    };
  },
  methods: {
    onApply(json, index) {
      this.advancedFilterInit(this.tableHeaders.length);
      this.advancedFilter[index] = json;
      this.tableHeaders[index].filterApplied = true;
      this.storeFilterConfiguration("request");
      this.fetch();
    },
    onClear(index) {
      this.advancedFilter[index] = [];
      this.tableHeaders[index].filterApplied = false;
      this.storeFilterConfiguration("request");
      this.fetch();
    },
    onChangeSort(value, field) {
      this.orderBy = field;
      this.orderDirection = value;
      this.sortOrder[0].sortField = field;
      this.sortOrder[0].direction = value;
      this.fetch();
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
        formatRange = this.viewParticipants;
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
    getParticipants(filter) {
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewParticipants.push(response.data.data[i].username);
        }
      });
    }
  }
};
export default PMColumnFilterPopoverRequestMixin;