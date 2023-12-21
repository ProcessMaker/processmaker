import PMColumnFilterPopoverCommonMixin from "../../common/PMColumnFilterPopoverCommonMixin.js";

const PMColumnFilterPopoverTasksMixin = {
  mixins: [PMColumnFilterPopoverCommonMixin],
  data() {
    return {
      viewAssignee: []
    };
  },
  methods: {
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
      //todo: Users can be numerous; therefore, the control should be a search 
      //field with a suggestion list type.
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewAssignee.push(response.data.data[i].username);
        }
      });
    }
  }
};
export default PMColumnFilterPopoverTasksMixin;