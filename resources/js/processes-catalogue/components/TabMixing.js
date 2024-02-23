export const methodsTabMixin = {
  methods: {
    jsonRows(rows) {
      if (rows.length === 0 || !_.has(_.head(rows), "_json")) {
        return rows;
      }
      return rows.map((row) => JSON.parse(row._json));
    },
    formatStatus(status) {
      let color = "success",
        label = "In Progress";
      switch (status) {
        case "DRAFT":
          color = "danger";
          label = "Draft";
          break;
        case "CANCELED":
          color = "danger";
          label = "Canceled";
          break;
        case "COMPLETED":
          color = "primary";
          label = "Completed";
          break;
        case "ERROR":
          color = "danger";
          label = "Error";
          break;
      }
      return (
        '<span class="badge badge-' +
        color +
        " status-" +
        color +
        '">' +
        this.$t(label) +
        "</span>"
      );
    },
  },
};
