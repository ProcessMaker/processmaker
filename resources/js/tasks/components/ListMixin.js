const ListMixin = {
  methods: {
    getSortParam() {
      if (this.sortOrder instanceof Array && this.sortOrder.length > 0) {
        return (
          `&order_by=${
            this.sortOrder[0].sortField
          }&order_direction=${
            this.sortOrder[0].direction}`
        );
      }
      return "";
    },

    fetch() {
      Vue.nextTick(() => {
        let pmql = "";

        if (this.pmql !== undefined) {
          pmql = this.pmql;
        }

        let { filter } = this;
        let filterParams = "";

        if (filter && filter.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
            filter = "";
          } else {
            filterParams = `&user_id=${
              window.ProcessMaker.user.id
            }&filter=${
              filter
            }&statusfilter=ACTIVE,CLOSED`;
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
            }&include=process,processRequest,processRequest.user,user,data`
              + `&pmql=${
                encodeURIComponent(pmql)
              }&per_page=${
                this.perPage
              }${filterParams
              }${this.getSortParam()
              }&non_system=true`,
          )
          .then((response) => {
            this.data = this.transform(response.data);
            if (this.$cookies.get("isMobile") === "true") {
              const dataIds = [];
              this.data.data.forEach((element) => {
                dataIds.push(element.id);
              });
              this.$cookies.set("tasksListMobile", JSON.stringify(dataIds));
            }
            this.$emit("in-overdue", response.data.meta.in_overdue);
          })
          .catch((error) => {
            window.ProcessMaker.alert(error.response.data.message, "danger");
            this.data = [];
          });
      });
    },
  },
};

export default ListMixin;
