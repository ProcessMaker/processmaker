export default {
  methods: {
    transform(data) {
      // Clean up fields for meta pagination so vue table pagination can understand
      data.meta.last_page = data.meta.total_pages;
      data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
      data.meta.to = data.meta.from + data.meta.count;
      data.data = this.jsonRows(data.data);

      for (const record of data.data) {
        // format Status
        record.owner = this.formatAvatar(record.user);
        record.category_list = this.formatCategory(record.categories);
      }
      return data;
    },
    showCreateTemplateModal(name, id) {
      this.processId = id;
      this.processTemplateName = name;
      this.$refs["create-template-modal"].show();
    },
    showPmBlockModal(name, id) {
      this.processId = id;
      this.pmBlockName = name;
      this.$refs["create-pm-block-modal"].show();
    },
    showAddToProjectModal(name, id) {
      this.processId = id;
      this.assetName = name;
      this.assetType = "process";
      this.$refs["add-to-project-modal"].show();
    },
    fetch() {
      Vue.nextTick(() => {
        if (this.cancelToken) {
          this.cancelToken();
          this.cancelToken = null;
        }
        const { CancelToken } = ProcessMaker.apiClient;

        this.loading = true;
        this.apiDataLoading = true;
        // change method sort by user
        this.orderBy = this.orderBy === "user" ? "user.firstname" : this.orderBy;
        // change method sort by slot name
        this.orderBy = this.orderBy === "__slot:updated_at" ? "updated_at" : this.orderBy;

        const url = this.status === null || this.status === "" || this.status === undefined
          ? "processes?"
          : `processes?status=${this.status}&`;

        let pmql = "";
        if (this.pmql !== undefined) {
          pmql = this.pmql;
        }

        let { filter } = this;

        if (filter?.length) {
          if (filter.isPMQL()) {
            pmql = `(${pmql}) and (${filter})`;
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
            `${url
            }page=${
              this.page
            }&per_page=${
              this.perPage
            }&pmql=${
              encodeURIComponent(pmql)
            }&filter=${
              this.filter
            }&order_by=${
              this.orderBy
            }&order_direction=${
              this.orderDirection
            }&include=categories,category,user`
            + "&with=events",
            {
              cancelToken: new CancelToken((c) => {
                this.cancelToken = c;
              }),
            },
          )
          .then((response) => {
            const data = this.addWarningMessages(response.data);
            this.data = this.transform(data);
            this.apiDataLoading = false;
            this.apiNoResults = false;
            this.loading = false;
          }).catch((error) => {
            if (error.code === "ERR_CANCELED") {
              return;
            }
            window.ProcessMaker.alert(error.response.data.message, "danger");
            this.data = [];
          });
      });
    },
    addWarningMessages(data) {
      data.data = data.data.map((process) => {
        process.warningMessages = [];
        if (!process.manager_id) {
          process.warningMessages.push(this.$t("Process Manager not configured."));
        }
        if (process.warnings) {
          process.warningMessages.push(this.$t("BPMN validation issues. Request cannot be started."));
        }
        return process;
      });
      return data;
    },
    handleEllipsisClick(processColumn) {
      this.fields.forEach(column => {
        if (column.field !== processColumn.field) {
          column.direction = "none";
          column.filterApplied = false;
        }
      });

      if (processColumn.direction === "asc") {
        processColumn.direction = "desc";
      } else if (processColumn.direction === "desc") {
        processColumn.direction = "none";
        processColumn.filterApplied = false;
      } else {
        processColumn.direction = "asc";
        processColumn.filterApplied = true;
      }

      if (processColumn.direction !== "none") {
        const sortOrder = [
          {
            sortField: processColumn.sortField || processColumn.field,
            direction: processColumn.direction,
          },
        ];
        this.dataManager(sortOrder);
      } else {
        this.fetch();
      }
    },
    openModeler(data) {
      return `/modeler/${data.id}`;
    },
  },
};
