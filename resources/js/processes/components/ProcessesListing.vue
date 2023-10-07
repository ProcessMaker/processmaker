<template>
  <div class="data-table">
    <data-loading
      v-show="shouldShowLoader"
      :for="/\/processes\?page/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div
      v-show="!shouldShowLoader"
      class="card card-body processes-table-card"
      data-cy="processes-table"
    >
      <vuetable
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        :fields="fields"
        :data="data"
        data-path="data"
        pagination-path="meta"
        :no-data-template="$t('No Data Available')"
        @vuetable:pagination-data="onPaginationData"
      >
        <template
          slot="name"
          slot-scope="props"
        >
          <i
            v-b-tooltip
            tabindex="0"
            :title="props.rowData.warningMessages.join(' ')"
            class="text-warning fa fa-exclamation-triangle"
            :class="{'invisible': props.rowData.warningMessages.length == 0}"
          />
          <i
            v-if="props.rowData.status == 'ACTIVE' || props.rowData.status == 'INACTIVE'"
            v-b-tooltip
            tabindex="0"
            :title="props.rowData.status"
            class="mr-2"
            :class="{ 'fas fa-check-circle text-success': props.rowData.status == 'ACTIVE', 'far fa-circle': props.rowData.status == 'INACTIVE' }"
          />
          <span v-uni-id="props.rowData.id.toString()">{{ props.rowData.name }}</span>
        </template>
        <template
          slot="owner"
          slot-scope="props"
        >
          <avatar-image
            class="d-inline-flex pull-left align-items-center"
            size="25"
            :input-data="props.rowData.user"
            hide-name="true"
          />
        </template>
        <template
          slot="actions"
          slot-scope="props"
        >
          <ellipsis-menu
            :actions="actions"
            :permission="permission"
            :data="props.rowData"
            :is-documenter-installed="isDocumenterInstalled"
            :divider="false"
            @navigate="onNavigate"
          />
        </template>
      </vuetable>
      <create-template-modal
        id="create-template-modal"
        ref="create-template-modal"
        asset-type="process"
        :current-user-id="currentUserId"
        :asset-name="processTemplateName"
        :asset-id="processId"
      />
      <create-pm-block-modal
        id="create-pm-block-modal"
        ref="create-pm-block-modal"
        :current-user-id="currentUserId"
        :asset-name="pmBlockName"
        :asset-id="processId"
      />
      <add-to-project-modal
        id="add-to-project-modal"
        ref="add-to-project-modal"
        asset-type="process"
        :asset-id="processId"
        :asset-name="assetName"
      />
      <pagination
        ref="pagination"
        :single="$t('Process')"
        :plural="$t('Processes')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
      />
    </div>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import TemplateExistsModal from "../../components/templates/TemplateExistsModal.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: {
    TemplateExistsModal, CreateTemplateModal, EllipsisMenu, CreatePmBlockModal, AddToProjectModal,
  },
  mixins: [datatableMixin, dataLoadingMixin, uniqIdsMixin],
  props: ["filter", "id", "status", "permission", "isDocumenterInstalled", "pmql", "processName", "currentUserId"],
  data() {
    return {
      actions: [
        {
          value: "unpause-start-timer", content: "Unpause Start Timer Events", icon: "fas fa-play", conditional: "if(has_timer_start_events and pause_timer_start, true, false)",
        },
        {
          value: "pause-start-timer", content: "Pause Start Timer Events", icon: "fas fa-pause", conditional: "if(has_timer_start_events and not(pause_timer_start), true, false)",
        },
        {
          value: "edit-designer", content: "Edit Process", link: true, href: "/modeler/{{id}}", permission: "edit-processes", icon: "fas fa-edit", conditional: "if(status == 'ACTIVE' or status == 'INACTIVE', true, false)",
        },
        {
          value: "create-template", content: "Save as Template", permission: "create-process-templates", icon: "fas fa-layer-group",
        },
        {
          value: "create-pm-block", content: "Save as PM Block", permission: "create-pm-blocks", icon: "fas nav-icon fa-cube",
        },
        { value: "add-to-project", content: "Add to Project", icon: "fas fa-folder-plus" },
        {
          value: "edit-item", content: "Configure", link: true, href: "/processes/{{id}}/edit", permission: "edit-processes", icon: "fas fa-cog", conditional: "if(status == 'ACTIVE' or status == 'INACTIVE', true, false)",
        },
        {
          value: "view-documentation", content: "View Documentation", link: true, href: "/modeler/{{id}}/print", permission: "view-processes", icon: "fas fa-sign", conditional: "isDocumenterInstalled",
        },
        {
          value: "remove-item", content: "Archive", permission: "archive-processes", icon: "fas fa-archive", conditional: "if(status == 'ACTIVE' or status == 'INACTIVE', true, false)",
        },
        { value: "divider" },
        {
          value: "export-item", content: "Export", link: true, href: "/processes/{{id}}/export", permission: "export-processes", icon: "fas fa-file-export",
        },
        {
          value: "restore-item", content: "Restore", permission: "archive-processes", icon: "fas fa-upload", conditional: "if(status == 'ARCHIVED', true, false)",
        },
        {
          value: "download-bpmn", content: "Download BPMN", permission: "view-processes", icon: "fas fa-file-download",
        },
      ],
      orderBy: "name",
      processId: null,
      processTemplateName: "",
      pmBlockName: "",
      assetName: "",
      processData: {},
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],

      fields: [
        {
          title: () => this.$t("Name"),
          name: "__slot:name",
          field: "name",
          sortField: "name",
        },
        {
          title: () => this.$t("Category"),
          name: "categories",
          sortField: "category.name",
          callback(categories) {
            return categories.map((item) => item.name).join(", ");
          },
        },
        {
          title: () => this.$t("Owner"),
          name: "__slot:owner",
          callback: this.formatUserName,
        },
        {
          title: () => this.$t("Modified"),
          name: "updated_at",
          sortField: "updated_at",
          callback: "formatDate",
        },
        {
          title: () => this.$t("Created"),
          name: "created_at",
          sortField: "created_at",
          callback: "formatDate",
        },
        {
          name: "__slot:actions",
          title: "",
        },
      ],
    };
  },
  created() {
    ProcessMaker.EventBus.$on("api-data-process", (val) => {
      this.fetch();
    });
  },
  methods: {
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
      this.$refs["add-to-project-modal"].show();
    },
    onNavigate(action, data) {
      const putData = {
        name: data.name,
        description: data.description,
      };
      switch (action.value) {
        case "unpause-start-timer":
          putData.pause_timer_start = false;
          ProcessMaker.apiClient
            .put(`processes/${data.id}`, putData)
            .then((response) => {
              ProcessMaker.alert(
                this.$t("The process was unpaused."),
                "success",
              );
              this.$emit("reload");
            });
          break;
        case "pause-start-timer":
          putData.pause_timer_start = true;
          ProcessMaker.apiClient
            .put(`processes/${data.id}`, putData)
            .then((response) => {
              ProcessMaker.alert(
                this.$t("The process was paused."),
                "success",
              );
              this.$emit("reload");
            });
          break;
        case "create-template":
          this.showCreateTemplateModal(data.name, data.id);
          break;
        case "create-pm-block":
          this.showPmBlockModal(data.name, data.id);
          break;
        case "restore-item":
          ProcessMaker.apiClient
            .put(`processes/${data.id}/restore`)
            .then((response) => {
              ProcessMaker.alert(
                this.$t("The process was restored."),
                "success",
              );
              this.$emit("reload");
            });
          break;
        case "remove-item":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            `${this.$t("Are you sure you want to archive the process")
                + data.name
            }?`,
            "",
            () => {
              ProcessMaker.apiClient
                .delete(`processes/${data.id}`)
                .then((response) => {
                  ProcessMaker.alert(
                    this.$t("The process was archived."),
                    "success",
                  );
                  this.$refs.pagination.loadPage(1);
                });
            },
          );
          break;

        case "download-bpmn":
          ProcessMaker.confirmModal(
            this.$t("Caution!"),
            this.$t("Are you sure you want to download the BPMN definition of the process?"),
            "",
            () => {
              ProcessMaker.apiClient
                .get(`processes/${data.id}/bpmn`)
                .then((response) => {
                  const link = document.createElement("a");
                  const file = new Blob([response.data], { type: "text/plain" });

                  link.href = URL.createObjectURL(file);
                  link.download = "bpmnProcess.xml";
                  link.click();
                  URL.revokeObjectURL(link.href);

                  ProcessMaker.alert(
                    this.$t("The process BPMN has been downloaded."),
                    "success",
                  );
                });
            },
          );
          break;
        case "add-to-project":
          this.showAddToProjectModal(data.name, data.id);
          break;
      }
    },
    formatStatus(status) {
      status = status.toLowerCase();
      const bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        draft: "text-warning",
        archived: "text-info",
      };
      const response = `<i class="fas fa-circle ${bubbleColor[status]} small"></i> `;
      status = status.charAt(0).toUpperCase() + status.slice(1);
      return `<div style="white-space:nowrap">${response}${status}</div>`;
    },
    formatUserName(user) {
      return (
        `${user.avatar
          ? this.createImg({
            src: user.avatar,
            class: "rounded-user",
          })
          : "<i class=\"fa fa-user rounded-user\"></i>"
        }<span>${
          user.fullname
        }</span>`
      );
    },
    createImg(properties) {
      const container = document.createElement("div");
      const node = document.createElement("img");
      for (const property in properties) {
        node.setAttribute(property, properties[property]);
      }
      container.appendChild(node);
      return container.innerHTML;
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
        this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

        const url = this.status === null || this.status === "" || this.status === undefined
          ? "processes?"
          : `processes?status=${this.status}&`;

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
  },
};
</script>

<style lang="scss" scoped>
  :deep(th#_updated_at) {
    width: 14%;
  }

  :deep(th#_created_at) {
    width: 14%;
  }

  .processes-table-card {
    padding: 0;
  }
</style>
