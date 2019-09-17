<template>
  <div class="data-table">
    <data-loading
            :for="/\/processes\?page|\/processes\?status/"
            v-show="shouldShowLoader"
            :empty="$t('No Data Available')"
            :empty-desc="$t('')"
            empty-icon="noData"
    />
    <div v-show="!shouldShowLoader"  class="card card-body table-card">
      <vuetable
              :dataManager="dataManager"
              :sortOrder="sortOrder"
              :css="css"
              :api-mode="false"
              @vuetable:pagination-data="onPaginationData"
              :fields="fields"
              :data="data"
              data-path="data"
              pagination-path="meta"
              :noDataTemplate="$t('No Data Available')"
      >
        <template slot="name" slot-scope="props">{{props.rowData.name}} <i v-if="props.rowData.warnings" class="text-warning fa fa-exclamation-triangle"></i></template>

        <template slot="owner" slot-scope="props">
          <avatar-image
                  class="d-inline-flex pull-left align-items-center"
                  size="25"
                  :input-data="props.rowData.user"
                  hide-name="true"
          ></avatar-image>
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                      variant="link"
                      @click="onAction('unpause-start-timer', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Play start timer events')"
                      v-if="props.rowData.has_timer_start_events && props.rowData.pause_timer_start"
              >
                <i class="fas fa-play fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                      variant="link"
                      @click="onAction('pause-start-timer', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Pause start timer events')"
                      v-if="props.rowData.has_timer_start_events && !props.rowData.pause_timer_start"
              >
                <i class="fas fa-pause fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                      variant="link"
                      @click="onAction('edit-designer', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Edit')"
                      v-if="permission.includes('edit-processes') && props.rowData.status === 'ACTIVE'"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                      variant="link"
                      @click="onAction('edit-item', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Configure')"
                      v-if="permission.includes('edit-processes') && props.rowData.status === 'ACTIVE'"
              >
                <i class="fas fa-cog fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                      variant="link"
                      @click="onAction('export-item', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Export')"
                      v-if="permission.includes('export-processes')"
              >
                <i class="fas fa-file-export fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                      variant="link"
                      @click="onAction('remove-item', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Archive')"
                      v-if="permission.includes('archive-processes') && props.rowData.status === 'ACTIVE'"
              >
                <i class="fas fa-download fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                      variant="link"
                      @click="onAction('restore-item', props.rowData, props.rowIndex)"
                      v-b-tooltip.hover
                      :title="$t('Restore')"
                      v-if="permission.includes('archive-processes') && props.rowData.status === 'INACTIVE'"
              >
                <i class="fas fa-upload fa-lg fa-fw"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>

      <pagination
              :single="$t('Process')"
              :plural="$t('Processes')"
              :perPageSelectEnabled="true"
              @changePerPage="changePerPage"
              @vuetable-pagination:change-page="onPageChange"
              ref="pagination"
      ></pagination>
    </div>
  </div>
</template>

<script>
  import datatableMixin from "../../components/common/mixins/datatable";
  import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";

  export default {
    mixins: [datatableMixin, dataLoadingMixin],
    props: ["filter", "id", "status", "permission"],
    data() {
      return {
        orderBy: "name",
        sortOrder: [
          {
            field: "name",
            sortField: "name",
            direction: "asc"
          }
        ],

        fields: [
          {
            title: () => this.$t("Name"),
            name: "__slot:name",
            field: "name",
            sortField: "name"
          },
          {
            title: () => this.$t("Category"),
            name: "category.name",
            sortField: "category.name"
          },
          {
            title: () => this.$t("Owner"),
            name: "__slot:owner",
            callback: this.formatUserName
          },
          {
            title: () => this.$t("Modified"),
            name: "updated_at",
            sortField: "updated_at",
            callback: "formatDate"
          },
          {
            title: () => this.$t("Created"),
            name: "created_at",
            sortField: "created_at",
            callback: "formatDate"
          },
          {
            name: "__slot:actions",
            title: ""
          }
        ]
      };
    },

    methods: {
      goToEdit(data) {
        window.location = "/processes/" + data + "/edit";
      },
      goToDesigner(data) {
        window.location = "/modeler/" + data;
      },
      goToExport(data) {
        window.location = "/processes/" + data + "/export";
      },
      onAction(action, data, index) {
        let putData;
        switch (action) {
          case "unpause-start-timer":
            putData = Object.assign({}, data);
            putData.pause_timer_start = false;
            delete putData.category;
            delete putData.user;
            ProcessMaker.apiClient
                .put("processes/" + data.id, putData)
                .then(response => {
                  ProcessMaker.alert(
                      this.$t("The process was restored."),
                      "success"
                  );
                  this.$emit("reload");
                });
            break;
          case "pause-start-timer":
            putData = Object.assign({}, data);
            putData.pause_timer_start = true;
            delete putData.category;
            delete putData.user;
            ProcessMaker.apiClient
                .put("processes/" + data.id, putData)
                .then(response => {
                  ProcessMaker.alert(
                      this.$t("The process was restored."),
                      "success"
                  );
                  this.$emit("reload");
                });
            break;
          case "edit-designer":
            this.goToDesigner(data.id);
            break;
          case "edit-item":
            this.goToEdit(data.id);
            break;
          case "export-item":
            this.goToExport(data.id);
            break;
          case "restore-item":
            ProcessMaker.apiClient
                .put("processes/" + data.id + "/restore")
                .then(response => {
                  ProcessMaker.alert(
                      this.$t("The process was restored."),
                      "success"
                  );
                  this.$emit("reload");
                });
            break;
          case "remove-item":
            ProcessMaker.confirmModal(
                this.$t("Caution!"),
                this.$t("Are you sure you want to archive the process") +
                data.name +
                "?",
                "",
                () => {
                  ProcessMaker.apiClient
                      .delete("processes/" + data.id)
                      .then(response => {
                        ProcessMaker.alert(
                            this.$t("The process was archived."),
                            "warning"
                        );
                        this.$emit("reload");
                      });
                }
            );
            break;
        }
      },
      formatStatus(status) {
        status = status.toLowerCase();
        let bubbleColor = {
          active: "text-success",
          inactive: "text-danger",
          draft: "text-warning",
          archived: "text-info"
        };
        let response =
            '<i class="fas fa-circle ' + bubbleColor[status] + ' small"></i> ';
        status = status.charAt(0).toUpperCase() + status.slice(1);
        return '<div style="white-space:nowrap">' + response + status + "</div>";
      },
      formatUserName(user) {
        return (
            (user.avatar
                ? this.createImg({
                  src: user.avatar,
                  class: "rounded-user"
                })
                : '<i class="fa fa-user rounded-user"></i>') +
            "<span>" +
            user.fullname +
            "</span>"
        );
      },
      createImg(properties) {
        let container = document.createElement("div");
        let node = document.createElement("img");
        for (let property in properties) {
          node.setAttribute(property, properties[property]);
        }
        container.appendChild(node);
        return container.innerHTML;
      },
      fetch() {
        this.loading = true;
        //change method sort by user
        this.orderBy = this.orderBy === "user" ? "user.firstname" : this.orderBy;
        //change method sort by slot name
        this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

        let url =
            this.status === null || this.status === "" || this.status === undefined
                ? "processes?"
                : "processes?status=" + this.status + "&";

        // Load from our api client
        ProcessMaker.apiClient
            .get(
                url +
                "page=" +
                this.page +
                "&per_page=" +
                this.perPage +
                "&filter=" +
                this.filter +
                "&order_by=" +
                this.orderBy +
                "&order_direction=" +
                this.orderDirection +
                "&include=category,user"
            )
            .then(response => {
              this.data = this.transform(response.data);
              this.loading = false;
            });
      }
    },

    computed: {}
  };
</script>

<style lang="scss" scoped>
  /deep/ th#_updated_at {
    width: 14%;
  }

  /deep/ th#_created_at {
    width: 14%;
  }
</style>
