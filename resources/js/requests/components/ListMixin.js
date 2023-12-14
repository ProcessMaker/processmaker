const ListMixin = {
  methods: {
    formatStatus(status) {
      let color = "success";
      let label = "In Progress";
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
        `<i class="fas fa-circle text-${
          color
        }"></i> <span>${
          this.$t(label)
        }</span>`
      );
    },
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      data.data = this.jsonRows(data.data);
      if (this.$cookies.get("isMobile") !== "true") {
        for (const record of data.data) {
          // format Status
          record.status = this.formatStatus(record.status);
        }
      }
      return data;
    },

    fetch() {
      Vue.nextTick(() => {
        let pmql = "";

        if (this.pmql !== undefined) {
          pmql = this.pmql;
        }

        let { filter } = this;

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = "";
          }
        }

        if (this.previousFilter !== filter) {
          this.page = 1;
        }

        this.previousFilter = filter;

        if (this.previousPmql !== pmql) {
          this.page = 1;
        }

        this.previousPmql = pmql;

        // Load from our api client
        ProcessMaker.apiClient
          .get(
            `${this.endpoint}?page=${
              this.page
            }&per_page=${
              this.perPage
            }&include=process,participants,data`
                  + `&pmql=${
                    encodeURIComponent(pmql)
                  }&filter=${
                    filter
                  }&order_by=${
                    this.orderBy === "__slot:ids" ? "id" : this.orderBy
                  }&order_direction=${
                    this.orderDirection
                  }${this.additionalParams}`,
          )
          .then((response) => {
            this.data = this.transform(response.data);
          }).catch((error) => {
            if (_.has(error, "response.data.message")) {
              ProcessMaker.alert(error.response.data.message, "danger");
            } else if (!(_.has(error, "response.data.error"))) {
              throw error;
            }
          });
      });
    },
  },
};

export default ListMixin;
