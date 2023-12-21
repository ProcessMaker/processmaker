import PMColumnFilterPopoverCommonMixin from "../../common/PMColumnFilterPopoverCommonMixin.js";

const PMColumnFilterPopoverRequestMixin = {
  mixins: [PMColumnFilterPopoverCommonMixin],
  data() {
    return {
      viewParticipants: []
    };
  },
  methods: {
    onChangeSort(value) {
      this.orderDirection = value;
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
      //todo: Users can be numerous; therefore, the control should be a search 
      //field with a suggestion list type.
      ProcessMaker.apiClient.get(this.getUrlUsers(filter)).then(response => {
        for (let i in response.data.data) {
          this.viewParticipants.push(response.data.data[i].username);
        }
      });
    }
  }
};
export default PMColumnFilterPopoverRequestMixin;